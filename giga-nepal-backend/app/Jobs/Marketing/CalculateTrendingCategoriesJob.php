<?php
namespace App\Jobs\Marketing;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue; use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels; use Illuminate\Support\Facades\Log;
class CalculateTrendingCategoriesJob implements ShouldQueue { use Dispatchable, InteractsWithQueue, Queueable, SerializesModels; public function __construct(public array $payload = []) {} public function handle(): void { Log::info('CalculateTrendingCategoriesJob placeholder executed', ['payload'=>$this->payload]); } }
