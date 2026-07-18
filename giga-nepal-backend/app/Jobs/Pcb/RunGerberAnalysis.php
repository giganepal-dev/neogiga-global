<?php

namespace App\Jobs\Pcb;

use App\Models\Pcb\PcbFile;
use App\Services\Pcb\PcbDfmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunGerberAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly PcbFile $file, private readonly int $userId) {}

    public function handle(PcbDfmService $dfm): void
    {
        if ($this->file->file_type !== 'gerber') {
            return;
        }

        try {
            $run = $dfm->analyze($this->file, $this->userId);

            if ($run->warnings()->where('severity', 'blocking')->exists()) {
                Log::warning('PCB DFM blocking issues detected.', [
                    'file_id' => $this->file->id,
                    'project_id' => $this->file->project_id,
                    'blocking_count' => $run->warnings()->where('severity', 'blocking')->count(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('PCB DFM analysis failed.', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
