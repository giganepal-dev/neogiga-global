<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neogiga:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive production health check for NeoGiga platform';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== NeoGiga Production Health Check ===');
        $this->info('Time: ' . now()->toIso8601String());
        $this->newLine();

        $issues = [];
        $warnings = [];

        // 1. Database Connection
        $this->task('Checking database connection', function () use (&$issues) {
            try {
                DB::connection()->getPdo();
                $this->info('✓ Database connected');
                
                // Check connection count
                $connections = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE datname = current_database()");
                if ($connections[0]->count > 80) {
                    throw new \Exception("High connection count: {$connections[0]->count}");
                }
                $this->info("  Active connections: {$connections[0]->count}");
            } catch (\Exception $e) {
                $issues[] = "Database: {$e->getMessage()}";
                $this->error('✗ Database connection failed: ' . $e->getMessage());
            }
        });

        // 2. Redis Connection
        $this->task('Checking Redis connection', function () use (&$issues, &$warnings) {
            try {
                $redisInfo = Cache::store('redis')->getRedis()->info();
                $this->info('✓ Redis connected');
                
                // Check memory usage
                $usedMemory = $redisInfo['used_memory'] ?? 0;
                $maxMemory = $redisInfo['maxmemory'] ?? 0;
                
                if ($maxMemory > 0 && $usedMemory > ($maxMemory * 0.9)) {
                    $warnings[] = "Redis memory usage high: " . round(($usedMemory / $maxMemory) * 100, 2) . '%';
                }
                
                $this->info("  Memory: " . round($usedMemory / 1024 / 1024, 2) . ' MB');
            } catch (\Exception $e) {
                $issues[] = "Redis: {$e->getMessage()}";
                $this->error('✗ Redis connection failed: ' . $e->getMessage());
            }
        });

        // 3. Disk Space
        $this->task('Checking disk space', function () use (&$issues, &$warnings) {
            $freeSpace = disk_free_space(base_path());
            $totalSpace = disk_total_space(base_path());
            $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
            
            $this->info("  Disk usage: " . round($usagePercent, 2) . '%');
            
            if ($usagePercent > 90) {
                $issues[] = "Critical disk usage: " . round($usagePercent, 2) . '%';
                $this->error('✗ Critical disk usage');
            } elseif ($usagePercent > 80) {
                $warnings[] = "High disk usage: " . round($usagePercent, 2) . '%';
                $this->warn('⚠ High disk usage');
            } else {
                $this->info('✓ Disk space OK');
            }
        });

        // 4. Queue Status
        $this->task('Checking queue status', function () use (&$issues, &$warnings) {
            try {
                $failedJobs = DB::table('failed_jobs')->count();
                $this->info("  Failed jobs: $failedJobs");
                
                if ($failedJobs > 10) {
                    $warnings[] = "High failed job count: $failedJobs";
                    $this->warn('⚠ High failed job count');
                } else {
                    $this->info('✓ Queue status OK');
                }
                
                // Check recent job failures (last hour)
                $recentFailures = DB::table('failed_jobs')
                    ->where('failed_at', '>=', now()->subHour())
                    ->count();
                    
                if ($recentFailures > 5) {
                    $warnings[] = "Recent job failures (1h): $recentFailures";
                }
            } catch (\Exception $e) {
                $issues[] = "Queue check: {$e->getMessage()}";
                $this->error('✗ Queue check failed');
            }
        });

        // 5. Storage Permissions
        $this->task('Checking storage permissions', function () use (&$issues) {
            $storagePath = storage_path();
            $writable = is_writable($storagePath);
            
            if (!$writable) {
                $issues[] = "Storage directory not writable: $storagePath";
                $this->error('✗ Storage not writable');
            } else {
                $this->info('✓ Storage writable');
            }
            
            // Check cache directory
            $cachePath = storage_path('framework/cache');
            if (!is_writable($cachePath)) {
                $issues[] = "Cache directory not writable: $cachePath";
                $this->error('✗ Cache directory not writable');
            }
        });

        // 6. Product Count Verification
        $this->task('Checking product catalog', function () use (&$issues, &$warnings) {
            try {
                $totalProducts = DB::table('products')->count();
                $approvedProducts = DB::table('products')->where('status', 'approved')->count();
                $draftProducts = DB::table('products')->where('status', 'draft')->count();
                
                $this->info("  Total products: " . number_format($totalProducts));
                $this->info("  Approved: " . number_format($approvedProducts));
                $this->info("  Draft: " . number_format($draftProducts));
                
                if ($totalProducts < 1000) {
                    $warnings[] = "Low product count: $totalProducts";
                }
                
                // Check for products without images
                $noImageCount = DB::table('products')
                    ->where('status', 'approved')
                    ->whereNull('primary_image')
                    ->orWhere('primary_image', '')
                    ->count();
                    
                if ($noImageCount > ($approvedProducts * 0.1)) {
                    $warnings[] = "More than 10% approved products without images: $noImageCount";
                }
            } catch (\Exception $e) {
                $issues[] = "Product catalog check: {$e->getMessage()}";
                $this->error('✗ Product catalog check failed');
            }
        });

        // 7. Search Index Status
        $this->task('Checking search index', function () use (&$issues, &$warnings) {
            try {
                $indexedProducts = DB::table('search_index')->count();
                $approvedProducts = DB::table('products')->where('status', 'approved')->count();
                
                $this->info("  Indexed products: " . number_format($indexedProducts));
                
                $coverage = $approvedProducts > 0 ? ($indexedProducts / $approvedProducts) * 100 : 0;
                $this->info("  Coverage: " . round($coverage, 2) . '%');
                
                if ($coverage < 95) {
                    $warnings[] = "Search index coverage low: " . round($coverage, 2) . '%';
                } else {
                    $this->info('✓ Search index OK');
                }
            } catch (\Exception $e) {
                $warnings[] = "Search index check: {$e->getMessage()}";
                $this->warn('⚠ Search index check skipped');
            }
        });

        // 8. SSL Certificate Expiry (if applicable)
        $this->task('Checking SSL certificate', function () use (&$issues, &$warnings) {
            if (isset($_SERVER['HTTPS']) || env('APP_URL', '').startsWith('https')) {
                try {
                    $url = parse_url(env('APP_URL'));
                    $host = $url['host'];
                    
                    $certificate = openssl_x509_parse(fopen("https://{$host}", 'r'));
                    
                    if ($certificate) {
                        $expiry = $certificate['validTo_time_t'];
                        $daysUntilExpiry = ($expiry - time()) / 86400;
                        
                        $this->info("  Expires in: " . round($daysUntilExpiry) . " days");
                        
                        if ($daysUntilExpiry < 14) {
                            $issues[] = "SSL certificate expires in " . round($daysUntilExpiry) . " days";
                            $this->error('✗ SSL certificate expiring soon');
                        } elseif ($daysUntilExpiry < 30) {
                            $warnings[] = "SSL certificate expires in " . round($daysUntilExpiry) . " days";
                            $this->warn('⚠ SSL certificate expiring soon');
                        } else {
                            $this->info('✓ SSL certificate OK');
                        }
                    }
                } catch (\Exception $e) {
                    $warnings[] = "SSL check: {$e->getMessage()}";
                    $this->warn('⚠ SSL check skipped');
                }
            }
        });

        // 9. Recent Error Log Check
        $this->task('Checking error logs', function () use (&$issues, &$warnings) {
            $logPath = storage_path('logs/laravel.log');
            
            if (file_exists($logPath)) {
                $recentErrors = 0;
                $handle = fopen($logPath, 'r');
                
                if ($handle) {
                    // Read last 100 lines
                    $lines = array_slice(file($logPath), -100);
                    foreach ($lines as $line) {
                        if (strpos($line, '[stacktrace]') !== false || strpos($line, 'CRITICAL') !== false) {
                            $recentErrors++;
                        }
                    }
                    fclose($handle);
                    
                    $this->info("  Recent errors (last 100 lines): $recentErrors");
                    
                    if ($recentErrors > 10) {
                        $warnings[] = "High recent error count: $recentErrors";
                        $this->warn('⚠ High error rate in logs');
                    } else {
                        $this->info('✓ Error log OK');
                    }
                }
            }
        });

        // Summary
        $this->newLine(2);
        $this->info('=== Health Check Summary ===');
        
        if (empty($issues) && empty($warnings)) {
            $this->info('✓ All checks passed!');
            return self::SUCCESS;
        }
        
        if (!empty($issues)) {
            $this->error('CRITICAL ISSUES (' . count($issues) . '):');
            foreach ($issues as $issue) {
                $this->error("  - $issue");
            }
        }
        
        if (!empty($warnings)) {
            $this->warn('WARNINGS (' . count($warnings) . '):');
            foreach ($warnings as $warning) {
                $this->warn("  - $warning");
            }
        }
        
        // Log results
        Log::channel('daily')->info('Health Check Completed', [
            'issues' => count($issues),
            'warnings' => count($warnings),
            'timestamp' => now()->toIso8601String(),
        ]);
        
        return !empty($issues) ? self::FAILURE : self::SUCCESS;
    }
}
