<?php

namespace App\Services\Pcb;

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
        'inner_copper_1' => ['\.G[1-9]\d*$', '\.g[1-9]\d*$', '\.IN[1-9]\d*$', 'layer[3-9]'],
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
            $dimensions = $this->estimateDimensions($layers);
            $warnings = $this->checkDfmRules($layers, $drillData, $entries);

            $run = PcbGerberAnalysisRun::create([
                'project_id' => $file->project_id,
                'file_id' => $file->id,
                'triggered_by_id' => $triggeredByUserId,
                'parser_version' => '1.0.0',
                'status' => 'completed',
                'detected_width_mm' => $dimensions['width_mm'],
                'detected_height_mm' => $dimensions['height_mm'],
                'detected_layer_count' => count(array_filter($layers, fn($l) => str_contains($l['detected_type'] ?? '', 'copper'))),
                'detected_hole_count' => $drillData['hole_count'],
                'detected_slot_count' => $drillData['slot_count'],
                'detected_min_drill_mm' => $drillData['min_drill_mm'],
                'detected_board_area_cm2' => $dimensions['area_cm2'],
                'has_castellated_indicator' => $this->hasFeature($entries, 'castellated'),
                'has_edge_plating_indicator' => $this->hasFeature($entries, 'edge.plat'),
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

        for ($i = 0; $i < $entryCount; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;

            $stat = $zip->statIndex($i);
            $size = $stat['size'] ?? 0;

            // Skip directories and hidden files
            if (str_ends_with($name, '/') || basename($name)[0] === '.' || str_starts_with($name, '__MACOSX')) {
                continue;
            }

            $entries[] = [
                'name' => $name,
                'basename' => basename($name),
                'size' => $size,
            ];

            // Extract only if within size limits
            $extractPath = $destDir.'/'.basename($name);
            if ($size < 10 * 1024 * 1024) { // 10MB per file max
                $zip->extractTo($destDir, $name);
            }
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
            'inner_copper_1' => 3,
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
        $drillEntry = collect($entries)->first(fn($e) => $this->matchLayerType($e['basename']) === 'drill');
        $result = ['hole_count' => 0, 'slot_count' => 0, 'min_drill_mm' => null, 'max_drill_mm' => null];

        if (!$drillEntry) return $result;

        $drillPath = $dir.'/'.$drillEntry['basename'];
        if (!file_exists($drillPath)) return $result;

        $content = @file_get_contents($drillPath);
        if (!$content) return $result;

        $lines = explode("\n", $content);
        $sizes = [];
        $inHeader = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '%' || $line === 'M48' || $line === 'M95' || $line === 'M30') continue;
            if ($line === 'M71' || $line === 'M72') continue; // metric/imperial mode

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

    private function estimateDimensions(array $layers): array
    {
        // ponytail: dimensions need actual Gerber parsing for accuracy.
        // We mark as needing engineering review. The admin enters dimensions manually.
        return [
            'width_mm' => null,
            'height_mm' => null,
            'area_cm2' => null,
        ];
    }

    private function checkDfmRules(array $layers, array $drillData, array $entries): array
    {
        $warnings = [];
        $detectedTypes = array_column($layers, 'detected_type');

        // Check for critical missing layers
        foreach (self::CRITICAL_LAYERS as $critical) {
            if (!in_array($critical, $detectedTypes, true)) {
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
        $unknownCount = count(array_filter($detectedTypes, fn($t) => $t === 'unknown'));
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
        if (!is_dir($dir)) return;
        foreach (glob($dir.'/*') as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
