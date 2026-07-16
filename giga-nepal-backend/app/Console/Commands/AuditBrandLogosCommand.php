<?php

namespace App\Console\Commands;

use App\Services\Catalog\BrandLogoAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditBrandLogosCommand extends Command
{
    protected $signature = 'catalog:audit-brand-logos {--output= : Relative storage path for generated reports} {--discover : Inspect configured official websites without changing records} {--limit= : Limit brands inspected}';

    protected $description = 'Generate a read-only official brand-logo audit and mapping plan.';

    public function handle(BrandLogoAuditService $audit): int
    {
        $output = trim((string) $this->option('output'), '/');
        $output = $output !== '' ? $output : 'private/brand-logo-audits/'.now()->format('Ymd-His');
        $result = $audit->audit((bool) $this->option('discover'), $this->option('limit') ? (int) $this->option('limit') : null);
        $disk = Storage::disk('local');
        $csv = $this->csv($result['rows']);
        $markdown = $this->markdown($result['summary'], $result['rows']);
        $disk->put($output.'/BRAND_LOGO_AUDIT.md', $markdown);
        $disk->put($output.'/BRAND_LOGO_MAPPING_PLAN.csv', $csv);
        $disk->put($output.'/SHA256SUMS', hash('sha256', $markdown).'  BRAND_LOGO_AUDIT.md'.PHP_EOL.hash('sha256', $csv).'  BRAND_LOGO_MAPPING_PLAN.csv'.PHP_EOL);

        $this->table(['Metric', 'Count'], collect($result['summary'])->map(fn ($value, $key) => [$key, $value])->all());
        $this->info('Brand logo dry-run completed. No brand, logo, or media records were changed.');
        $this->line('audit: storage/app/'.$output.'/BRAND_LOGO_AUDIT.md');
        $this->line('mapping_plan: storage/app/'.$output.'/BRAND_LOGO_MAPPING_PLAN.csv');

        return self::SUCCESS;
    }

    private function markdown(array $summary, array $rows): string
    {
        $lines = ['# NeoGiga Brand Logo Audit', '', '## Summary', ''];
        foreach ($summary as $key => $value) {
            $lines[] = '- '.str_replace('_', ' ', $key).': '.$value;
        }
        $lines[] = '';
        $lines[] = '## Dry-run guard';
        $lines[] = '';
        $lines[] = 'No brand, product, SEO, image, or media record was changed. Proposed URLs require explicit review before staging or approval.';
        $lines[] = '';
        $lines[] = '## Mapping preview';
        $lines[] = '';
        $lines[] = '| Brand | Proposed domain | Confidence | Action |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach (array_slice($rows, 0, 30) as $row) {
            $lines[] = '| '.$row['brand_name'].' | '.($row['official_domain'] ?: 'Manual review').' | '.number_format((float) $row['confidence'], 2).' | '.$row['action'].' |';
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['brand_id', 'brand_name', 'slug', 'existing_logo', 'logo_verified', 'proposed_official_domain', 'proposed_logo_url', 'source_type', 'confidence', 'action', 'review_note']);
        foreach ($rows as $row) {
            fputcsv($stream, [$row['brand_id'], $row['brand_name'], $row['slug'], $row['existing_logo'], $row['logo_verified'] ? 'yes' : 'no', $row['official_domain'], $row['proposed_logo_url'], $row['source_type'], $row['confidence'], $row['action'], $row['review_note']]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }
}
