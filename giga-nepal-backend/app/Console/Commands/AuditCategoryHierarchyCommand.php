<?php

namespace App\Console\Commands;

use App\Services\Catalog\CategoryHierarchyAuditService;
use Illuminate\Console\Command;

class AuditCategoryHierarchyCommand extends Command
{
    protected $signature = 'catalog:audit-category-hierarchy {--output= : Directory for dry-run reports}';

    protected $description = 'Generate a read-only category hierarchy and product remap plan.';

    public function handle(CategoryHierarchyAuditService $audit): int
    {
        $directory = (string) ($this->option('output') ?: storage_path('reports/category-hierarchy'));
        $result = $audit->write($directory);
        $this->info('Category hierarchy dry-run completed. No category or product records were changed.');
        $this->table(['Metric', 'Count'], collect($result['summary'])->map(fn ($value, $key) => [$key, $value])->all());
        foreach ($result['files'] as $name => $path) {
            $this->line($name.': '.$path);
        }
        $this->line('plan_sha256: '.$result['planHash']);

        return self::SUCCESS;
    }
}
