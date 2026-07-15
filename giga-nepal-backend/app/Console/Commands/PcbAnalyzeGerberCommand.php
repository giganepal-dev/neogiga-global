<?php

namespace App\Console\Commands;

use App\Models\Pcb\PcbFile;
use App\Services\Pcb\PcbDfmService;
use Illuminate\Console\Command;

class PcbAnalyzeGerberCommand extends Command
{
    protected $signature = 'pcb:analyze-gerber
                            {--file= : Analyze a specific file UUID}
                            {--pending : Analyze all files with pending status}
                            {--user=1 : User ID to attribute the analysis to}';

    protected $description = 'Run DFM analysis on Gerber files';

    public function handle(PcbDfmService $dfm): int
    {
        if ($fileId = $this->option('file')) {
            return $this->analyzeOne($dfm, $fileId);
        }

        if ($this->option('pending')) {
            return $this->analyzePending($dfm);
        }

        $this->info('Usage: pcb:analyze-gerber --file=<uuid> or --pending');
        return self::FAILURE;
    }

    private function analyzeOne(PcbDfmService $dfm, string $fileId): int
    {
        $file = PcbFile::find($fileId);
        if (!$file) {
            $this->error("File {$fileId} not found.");
            return self::FAILURE;
        }

        if ($file->file_type !== 'gerber') {
            $this->warn("File {$file->filename_original} is type '{$file->file_type}', not 'gerber'.");
            return self::FAILURE;
        }

        $this->info("Analyzing: {$file->filename_original} ({$file->id})");
        $userId = (int) $this->option('user');

        try {
            $run = $dfm->analyze($file, $userId);
            $this->info("Analysis complete. Run ID: {$run->id}");
            $this->info("  Layers: {$run->detected_layers_count} detected");
            $this->info("  Holes: {$run->detected_hole_count}");
            $this->info("  Warnings: ".$run->warnings()->count());
            $this->info("  Confidence: {$run->confidence_level}");

            foreach ($run->warnings as $warning) {
                $level = $warning->severity === 'blocking' ? 'error' : ($warning->severity === 'warning' ? 'warn' : 'info');
                $this->{$level}("  [{$warning->warning_code}] {$warning->message}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Analysis failed: {$e->getMessage()}");
            $file->update(['processing_status' => 'failed', 'processing_error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    private function analyzePending(PcbDfmService $dfm): int
    {
        $files = PcbFile::where('file_type', 'gerber')
            ->where('processing_status', 'pending')
            ->limit(20)
            ->get();

        if ($files->isEmpty()) {
            $this->info('No pending Gerber files to analyze.');
            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} pending file(s).");
        $userId = (int) $this->option('user');
        $success = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                $file->update(['processing_status' => 'processing']);
                $dfm->analyze($file, $userId);
                $success++;
                $this->line("  ✓ {$file->filename_original}");
            } catch (\Exception $e) {
                $failed++;
                $file->update(['processing_status' => 'failed', 'processing_error' => $e->getMessage()]);
                $this->warn("  ✗ {$file->filename_original}: {$e->getMessage()}");
            }
        }

        $this->info("Done: {$success} succeeded, {$failed} failed.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
