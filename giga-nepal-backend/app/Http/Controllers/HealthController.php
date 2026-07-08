<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => $this->appCheck(),
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'queue' => $this->queueCheck(),
            'storage' => $this->storageCheck(),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['ok'] === true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function appCheck(): array
    {
        return [
            'ok' => true,
            'env' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'url_configured' => filled(config('app.url')),
        ];
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('select 1 as health_check');

            return [
                'ok' => true,
                'connection' => config('database.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'connection' => config('database.default'),
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function cacheCheck(): array
    {
        $key = 'neogiga:health:' . app()->environment();

        try {
            Cache::put($key, now()->toIso8601String(), 60);

            return [
                'ok' => Cache::has($key),
                'store' => config('cache.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'store' => config('cache.default'),
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function queueCheck(): array
    {
        $queue = config('queue.default');

        try {
            $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;

            return [
                'ok' => true,
                'connection' => $queue,
                'pending_jobs' => $pendingJobs,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'connection' => $queue,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function storageCheck(): array
    {
        $paths = [
            'framework' => storage_path('framework'),
            'logs' => storage_path('logs'),
            'cache' => base_path('bootstrap/cache'),
        ];

        $checks = collect($paths)->mapWithKeys(fn (string $path, string $name): array => [
            $name => is_dir($path) && is_writable($path),
        ])->all();

        return [
            'ok' => ! in_array(false, $checks, true),
            'paths' => $checks,
        ];
    }
}
