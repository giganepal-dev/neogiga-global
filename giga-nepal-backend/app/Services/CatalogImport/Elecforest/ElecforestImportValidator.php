<?php

namespace App\Services\CatalogImport\Elecforest;

use Illuminate\Support\Facades\DB;

class ElecforestImportValidator
{
    /** @param array<string, mixed> $record @return list<string> */
    public function validate(array $record): array
    {
        $errors = [];
        if ($record['source_name'] === '') {
            $errors[] = 'Missing product name.';
        }
        if ($record['source_url'] === '' || parse_url((string) $record['source_url'], PHP_URL_SCHEME) !== 'https') {
            $errors[] = 'Missing or non-HTTPS product URL.';
        }
        if (! in_array(strtolower((string) parse_url((string) $record['source_url'], PHP_URL_HOST)), ['elecforest.com', 'www.elecforest.com'], true)) {
            $errors[] = 'Product URL is not on the ElecForest allowlist.';
        }
        if (! empty($record['is_collection_page'])) {
            $errors[] = 'Collection page is not a sellable product record.';
        }
        if (mb_strlen((string) $record['source_name']) > 240) {
            $errors[] = 'Product name exceeds 240 characters.';
        }

        return $errors;
    }

    /** @param object $product @return list<string> */
    public function publicationFailures(object $product): array
    {
        $failures = [];
        foreach (['category_id' => 'category', 'manufacturer_id' => 'manufacturer'] as $column => $label) {
            if (empty($product->{$column})) {
                $failures[] = "Missing verified {$label}.";
            }
        }
        if (empty($product->short_description) || empty($product->description)) {
            $failures[] = 'Content is incomplete.';
        }
        if (! DB::table('product_images')->where('product_id', $product->id)->where('is_active', true)->exists()) {
            $failures[] = 'No rights-approved active image.';
        }
        if (! DB::table('product_seo_meta')->where('product_id', $product->id)->whereNotNull('meta_description')->exists()) {
            $failures[] = 'SEO metadata is incomplete.';
        }
        if (DB::table('catalog_review_tasks')->where('product_id', $product->id)->where('status', 'open')->exists()) {
            $failures[] = 'Open catalog review tasks remain.';
        }

        return $failures;
    }
}
