<?php

namespace App\Services\Pcb;

use App\Models\Pcb\PcbDetectedLayer;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbGerberAnalysisRun;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PcbDfmService
{
    /** Gerber filename → layer type mapping */
    private const LAYER_PATTERNS = [
        'top_copper' => ['\.GTL$', '\.gtl$', '\.TOP$', 'top.*\.gbr$', '.*top.*copper', 'layer1.*copper', '\.C1\.'],
        'bottom_copper' => ['\.GBL$', '\.gbl$', '\.BOT$', 'bottom.*\.gbr$', '.*bottom.*copper', 'layer2.*copper', '\.C2\.'],
        'inner_copper' => ['\.G[1-9]\d*$', '\.g[1-9]\d*$', '\.IN[1-9]\d*$', 'layer[3-9]'],
        'top_solder_mask' => ['\.GTS$', '\.gts$', '\.SMT$', '.*mask.*top', '.*top.*mask'],
        'bottom_solder_mask' => ['\.GBS$', '\.gbs$', '\.SMB$', '.*mask.*bot', '.*bottom.*mask'],
        'top_silkscreen' => ['\.GTO$', '\.gto$', '\.SST$', '.*silk.*top', '.*top.*silk'],
        'bottom_silkscreen' => ['\.GBO$', '\.gbo$', '\.SSB$', '.*silk.*bot', '.*bottom.*silk'],
        'top_paste' => ['\.GTP$', '\.gtp$', '\.SPT$', '.*paste.*top', '.*top.*paste'],
        'bottom_paste' => ['\.GBP$', '\.gbp$', '\.SPB$', '.*paste.*bot', '.*bottom.*paste'],
        'board_outline' => ['\.GKO$', '\.gko$', '\.GM1$', '\.gm1$', '.*outline', '.*board.*outline', '.*profile', '\.(?:DIM|MIL|OUT)'],
        'drill' => ['\.TXT$', '\.txt$', '\.DRL$', '\.drl$', '\.NCD$', '.*drill', '.*pth', '.*npth'],
        'mechanical' => ['\.GM\d+$', '\.gm\d+$', '.*mech', '.*fab'],
    ];

    private const CRITICAL_LAYERS = ['top_copper', 'bottom_copper', 'board_outline', 'drill'];

    /**
     * Analyze a Gerber ZIP file and populate pcb_gerber_analysis_runs.
     */
    public function analyze(PcbFile $file, int $triggeredByUserId): PcbGerberAnalysisRun
    {
        $disk = $file->storage_disk;
        $path = $file->storage_path;

        abort_unless(Storage::disk($disk)->exists($path), 404, 'File not found on storage.');

        $tmpDir = sys_get_temp_dir().'/pcb-gerber-'.$file->id.'-'.time();
        mkdir($tmpDir, 0755, true);

        try {
            $entries = $this->extractArchive(Storage::disk($disk)->path($path), $tmpDir);
            $layers = $this->detectLayers($entries);
            $drillData = $this->parseDrillFile($tmpDir, $entries);
            $dimensions = $this->estimateDimensions($layers, $entries);
            $warnings = $this->checkDfmRules($layers, $drillData, $entries);

            $run = PcbGerberAnalysisRun::create([
                'project_id' => $file->project_id,
                'file_id' => $file->id,
                'triggered_by_id' => $triggeredByUserId,
                'parser_version' => '1.1.0',
                'status' => 'completed',
                'detected_width_mm' => $dimensions['width_mm'],
                'detected_height_mm' => $dimensions['height_mm'],
                'detected_layer_count' => count(array_filter($layers, fn ($l) => str_contains($l['detected_type'] ?? '', 'copper'))),
                'detected_hole_count' => $drillData['hole_count'],
                'detected_slot_count' => $drillData['slot_count'],
                'detected_min_drill_mm' => $drillData['min_drill_mm'],
                'detected_board_area_cm2' => $dimensions['area_cm2'],
                'has_castellated_indicator' => $this->hasFeature($entries, 'castellated'),
                'has_edge_plating_indicator' => $this->hasFeature($entries, 'edge.plat'),
                'has_panelization_indicator' => $this->hasFeature($entries, 'panel'),
                'confidence_level' => $dimensions['width_mm'] ? 'medium' : 'low',
            ]);

            foreach ($layers as $layer) {
                $run->detectedLayers()->create($layer);
            }

            foreach ($warnings as $warning) {
                $run->warnings()->create($warning);
            }

            $file->update(['processing_status' => 'completed']);

            return $run;
        } finally {
            $this->cleanup($tmpDir);
        }
    }

    public function layerContents(PcbFile $file, PcbDetectedLayer $layer): string
    {
        abort_unless($file->file_type === 'gerber', 404);

        $temporaryPath = null;
        try {
            try {
                $archivePath = Storage::disk($file->storage_disk)->path($file->storage_path);
            } catch (\Throwable) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'pcb-layer-');
                abort_unless($temporaryPath, 500, 'Unable to prepare Gerber preview.');
                $input = Storage::disk($file->storage_disk)->readStream($file->storage_path);
                $output = fopen($temporaryPath, 'wb');
                abort_unless(is_resource($input) && is_resource($output), 404);
                stream_copy_to_stream($input, $output);
                fclose($input);
                fclose($output);
                $archivePath = $temporaryPath;
            }

            $zip = new ZipArchive;
            abort_unless($zip->open($archivePath) === true, 422, 'Gerber archive cannot be opened.');
            try {
                $archiveEntry = $layer->metadata['archive_path'] ?? null;
                $index = is_string($archiveEntry) ? $zip->locateName($archiveEntry) : false;
                if ($index === false) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        if (basename((string) $zip->getNameIndex($i)) === $layer->filename) {
                            $index = $i;
                            break;
                        }
                    }
                }

                abort_if($index === false, 404, 'Gerber layer was not found in the archive.');
                $stat = $zip->statIndex($index);
                abort_if(($stat['size'] ?? 0) > 10 * 1024 * 1024, 413, 'Gerber layer is too large to preview.');
                $contents = $zip->getFromIndex($index);
                abort_if($contents === false, 422, 'Gerber layer could not be read.');

                return $contents;
            } finally {
                $zip->close();
            }
        } finally {
            if ($temporaryPath) {
                @unlink($temporaryPath);
            }
        }
    }

    private function extractArchive(string $archivePath, string $destDir): array
    {
        $zip = new ZipArchive;
        $entries = [];

        if ($zip->open($archivePath) !== true) {
            return $entries;
        }

        // Safety: check archive stats before extracting
        $entryCount = $zip->numFiles;
        $maxEntries = (int) config('pcb.max_archive_entries', 2000);
        if ($entryCount > $maxEntries) {
            $zip->close();
            abort(422, "Archive contains {$entryCount} entries (max {$maxEntries}).");
        }

        $totalUncompressed = 0;
        for ($i = 0; $i < $entryCount; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }

            $stat = $zip->statIndex($i);
            $size = $stat['size'] ?? 0;
            $compressedSize = max(1, (int) ($stat['comp_size'] ?? 1));
            $totalUncompressed += $size;

            abort_if(
                $totalUncompressed > (int) config('pcb.max_archive_uncompressed_mb', 500) * 1024 * 1024,
                422,
                'Gerber archive expands beyond the configured safety limit.'
            );
            abort_if(
                $size > 1024 * 1024 && ($size / $compressedSize) > (int) config('pcb.max_archive_ratio', 100),
                422,
                'Gerber archive contains an unsafe compression ratio.'
            );

            // Skip directories and hidden files
            if (str_ends_with($name, '/') || basename($name)[0] === '.' || str_starts_with($name, '__MACOSX')) {
                continue;
            }

            $entry = [
                'name' => $name,
                'basename' => basename($name),
                'size' => $size,
            ];

            // Copy only the entry contents into a generated flat path. Never let
            // archive-controlled paths reach the filesystem.
            if ($size <= 10 * 1024 * 1024) {
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    $entry['extracted_path'] = $destDir.'/'.sha1($name).'-'.basename($name);
                    file_put_contents($entry['extracted_path'], $contents);
                }
            }
            $entries[] = $entry;
        }

        $zip->close();

        return $entries;
    }

    private function detectLayers(array $entries): array
    {
        $layers = [];

        foreach ($entries as $entry) {
            $basename = $entry['basename'];
            $detectedType = $this->matchLayerType($basename);
            $matched = $detectedType !== 'unknown';

            $layers[] = [
                'filename' => $basename,
                'detected_type' => $detectedType,
                'is_matched' => $matched,
                'layer_order' => $matched ? $this->layerOrder($detectedType) : null,
                'metadata' => [
                    'archive_path' => $entry['name'],
                    'size_bytes' => $entry['size'],
                ],
            ];
        }

        return $layers;
    }

    private function matchLayerType(string $filename): string
    {
        foreach (self::LAYER_PATTERNS as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/i', $filename)) {
                    return $type;
                }
            }
        }

        return 'unknown';
    }

    private function layerOrder(string $type): ?int
    {
        return match ($type) {
            'top_copper' => 1,
            'bottom_copper' => 2,
            'inner_copper' => 3,
            'board_outline' => 10,
            'drill' => 11,
            'top_solder_mask' => 20,
            'bottom_solder_mask' => 21,
            'top_silkscreen' => 30,
            'bottom_silkscreen' => 31,
            'top_paste' => 40,
            'bottom_paste' => 41,
            default => 99,
        };
    }

    private function parseDrillFile(string $dir, array $entries): array
    {
        $drillEntry = collect($entries)->first(fn ($e) => $this->matchLayerType($e['basename']) === 'drill');
        $result = ['hole_count' => 0, 'slot_count' => 0, 'min_drill_mm' => null, 'max_drill_mm' => null];

        if (! $drillEntry) {
            return $result;
        }

        $drillPath = $drillEntry['extracted_path'] ?? null;
        if (! $drillPath || ! file_exists($drillPath)) {
            return $result;
        }

        $content = @file_get_contents($drillPath);
        if (! $content) {
            return $result;
        }

        $lines = explode("\n", $content);
        $sizes = [];
        $inHeader = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '%' || $line === 'M48' || $line === 'M95' || $line === 'M30') {
                continue;
            }
            if ($line === 'M71' || $line === 'M72') {
                continue;
            } // metric/imperial mode

            // Tool definition: T01C0.035 or T01 0.035in or T1 0.9mm
            if (preg_match('/^T(\d+)\s*(?:C|F)?\s*([\d.]+)/i', $line, $m)) {
                $size = (float) $m[2];
                $sizes[] = $size;

                continue;
            }

            // Hole coordinates
            if (preg_match('/^X([\d.-]+)Y([\d.-]+)/i', $line)) {
                $result['hole_count']++;

                continue;
            }

            // Slot: G85
            if (str_contains($line, 'G85')) {
                $result['slot_count']++;

                continue;
            }
        }

        if ($sizes) {
            $result['min_drill_mm'] = round(min($sizes), 3);
            $result['max_drill_mm'] = round(max($sizes), 3);
        }

        return $result;
    }

    private function estimateDimensions(array $layers, array $entries): array
    {
        $outline = collect($layers)->firstWhere('detected_type', 'board_outline');
        $archivePath = $outline['metadata']['archive_path'] ?? null;
        $entry = collect($entries)->first(fn (array $candidate) => ($archivePath && $candidate['name'] === $archivePath)
            || (! $archivePath && $outline && $candidate['basename'] === $outline['filename'])
        );
        $contents = $entry && isset($entry['extracted_path'])
            ? @file_get_contents($entry['extracted_path'])
            : false;

        if (! is_string($contents) || $contents === '') {
            return ['width_mm' => null, 'height_mm' => null, 'area_cm2' => null];
        }

        $bounds = $this->gerberCoordinateBounds($contents);
        if (! $bounds) {
            return ['width_mm' => null, 'height_mm' => null, 'area_cm2' => null];
        }

        $width = round($bounds['max_x'] - $bounds['min_x'], 4);
        $height = round($bounds['max_y'] - $bounds['min_y'], 4);
        if ($width <= 0 || $height <= 0 || $width > 2000 || $height > 2000) {
            return ['width_mm' => null, 'height_mm' => null, 'area_cm2' => null];
        }

        return [
            'width_mm' => $width,
            'height_mm' => $height,
            'area_cm2' => round(($width * $height) / 100, 4),
        ];
    }

    private function gerberCoordinateBounds(string $contents): ?array
    {
        $unitFactor = preg_match('/(?:%MOIN\*%|G70\*)/i', $contents) ? 25.4 : 1.0;
        $zeroSuppression = 'L';
        $xInteger = $yInteger = 2;
        $xDecimal = $yDecimal = 4;
        if (preg_match('/%FS([LT])A?X(\d)(\d)Y(\d)(\d)\*%/i', $contents, $format)) {
            [, $zeroSuppression, $xInteger, $xDecimal, $yInteger, $yDecimal] = $format;
        }

        $currentX = $currentY = null;
        $operation = 2;
        $points = [];
        foreach (explode('*', str_replace("\r", '', $contents)) as $rawCommand) {
            $command = ltrim(trim($rawCommand), "%\n ");
            if (! preg_match('/^(?:G0?[123])?[XY]/i', $command)) {
                continue;
            }
            if (preg_match('/D0?([123])(?:$|[^0-9])/i', $command, $operationMatch)) {
                $operation = (int) $operationMatch[1];
            }
            if (preg_match('/X([+-]?[\d.]+)/i', $command, $xMatch)) {
                $currentX = $this->decodeGerberCoordinate($xMatch[1], (int) $xInteger, (int) $xDecimal, $zeroSuppression) * $unitFactor;
            }
            if (preg_match('/Y([+-]?[\d.]+)/i', $command, $yMatch)) {
                $currentY = $this->decodeGerberCoordinate($yMatch[1], (int) $yInteger, (int) $yDecimal, $zeroSuppression) * $unitFactor;
            }
            if (in_array($operation, [1, 2], true) && $currentX !== null && $currentY !== null) {
                $points[] = [$currentX, $currentY];
            }
        }

        if (count($points) < 2) {
            return null;
        }

        $x = array_column($points, 0);
        $y = array_column($points, 1);

        return ['min_x' => min($x), 'max_x' => max($x), 'min_y' => min($y), 'max_y' => max($y)];
    }

    private function decodeGerberCoordinate(string $raw, int $integerDigits, int $decimalDigits, string $zeroSuppression): float
    {
        if (str_contains($raw, '.')) {
            return (float) $raw;
        }

        $negative = str_starts_with($raw, '-');
        $digits = ltrim($raw, '+-');
        $length = $integerDigits + $decimalDigits;
        $digits = strtoupper($zeroSuppression) === 'T'
            ? str_pad($digits, $length, '0', STR_PAD_RIGHT)
            : str_pad($digits, $length, '0', STR_PAD_LEFT);
        $value = ((float) $digits) / (10 ** $decimalDigits);

        return $negative ? -$value : $value;
    }

    private function checkDfmRules(array $layers, array $drillData, array $entries): array
    {
        $warnings = [];
        $detectedTypes = array_column($layers, 'detected_type');

        // Check for critical missing layers
        foreach (self::CRITICAL_LAYERS as $critical) {
            if (! in_array($critical, $detectedTypes, true)) {
                if ($critical === 'bottom_copper' && in_array('top_copper', $detectedTypes, true)) {
                    $warnings[] = [
                        'severity' => 'info',
                        'warning_code' => 'SINGLE_SIDED',
                        'message' => 'Single-sided board detected. No bottom copper layer found. If this is a multi-layer design, the bottom copper layer may use a non-standard filename.',
                    ];
                } elseif ($critical === 'board_outline') {
                    $warnings[] = [
                        'severity' => 'blocking',
                        'warning_code' => 'MISSING_OUTLINE',
                        'message' => 'No board outline layer detected. Required for manufacturing. Add a .GKO/.GM1 file.',
                    ];
                } elseif ($critical === 'drill') {
                    $warnings[] = [
                        'severity' => 'blocking',
                        'warning_code' => 'MISSING_DRILL',
                        'message' => 'No drill file detected. Required for hole fabrication. Add an Excellon .TXT/.DRL file.',
                    ];
                }
            }
        }

        // Check for unknown layers
        $unknownCount = count(array_filter($detectedTypes, fn ($t) => $t === 'unknown'));
        if ($unknownCount > 0) {
            $warnings[] = [
                'severity' => 'warning',
                'warning_code' => 'UNKNOWN_LAYERS',
                'message' => "{$unknownCount} file(s) could not be identified by naming convention. They may be documentation or non-standard layers.",
            ];
        }

        // Drill checks
        if ($drillData['min_drill_mm'] !== null && $drillData['min_drill_mm'] < 0.15) {
            $warnings[] = [
                'severity' => 'warning',
                'warning_code' => 'SMALL_DRILL',
                'message' => 'Minimum drill size '.$drillData['min_drill_mm'].'mm is below the standard 0.15mm minimum. Engineering review required.',
            ];
        }

        if ($drillData['max_drill_mm'] !== null && $drillData['max_drill_mm'] > 6.3) {
            $warnings[] = [
                'severity' => 'warning',
                'warning_code' => 'LARGE_DRILL',
                'message' => 'Maximum drill size '.$drillData['max_drill_mm'].'mm exceeds standard 6.3mm. Engineering review required.',
            ];
        }

        // File count sanity check
        if (count($entries) === 0) {
            $warnings[] = [
                'severity' => 'blocking',
                'warning_code' => 'EMPTY_ARCHIVE',
                'message' => 'No files found in the archive. Check the ZIP is valid and not corrupted.',
            ];
        }

        // Slot warning
        if ($drillData['slot_count'] > 0) {
            $warnings[] = [
                'severity' => 'info',
                'warning_code' => 'SLOTS_DETECTED',
                'message' => $drillData['slot_count'].' slot(s) detected. Slots must have rounded ends (minimum radius 0.5mm).',
            ];
        }

        return $warnings;
    }

    private function hasFeature(array $entries, string $indicator): bool
    {
        foreach ($entries as $entry) {
            if (stripos($entry['name'], $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function cleanup(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.'/*') as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
