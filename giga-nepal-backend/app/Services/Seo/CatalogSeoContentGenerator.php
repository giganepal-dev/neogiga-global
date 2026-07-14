<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class CatalogSeoContentGenerator
{
    public function product(object|array $product, string $country = 'Global', string $websiteTag = 'Engineering Marketplace'): array
    {
        $p = (object) $product;
        $name = $this->clean($p->name ?? $p->product_name ?? 'Product');
        $brand = $this->clean($p->brand_name ?? $p->brand ?? '');
        $manufacturer = $this->clean($p->manufacturer_record_name ?? $p->manufacturer_name ?? $brand ?: 'manufacturer');
        $category = $this->clean($p->category_name ?? $p->category ?? 'engineering component');
        $mpn = $this->clean($p->mpn ?? '');
        $sku = $this->clean($p->sku ?? '');

        $brandPrefix = $brand !== '' ? $brand . ' ' : '';
        $mpnPart = $mpn !== '' ? ' ' . $mpn : '';
        $title = "{$brandPrefix}{$name}{$mpnPart} on NeoGiga {$country} - {$websiteTag}";

        $identifier = $mpn !== '' ? "MPN {$mpn}" : ($sku !== '' ? "SKU {$sku}" : 'verified product data');
        $description = "Source {$name} ({$identifier}) from {$manufacturer} on NeoGiga {$country}. Compare catalog details, RFQ sourcing, regional availability and engineering procurement support.";

        $short = "{$name} is a {$category} from {$manufacturer} for NeoGiga {$country} sourcing, BOM review and RFQ procurement.";
        if ($mpn !== '') {
            $short = "{$name} ({$mpn}) is a {$category} from {$manufacturer} for NeoGiga {$country} sourcing, BOM review and RFQ procurement.";
        }

        $long = "{$name} is listed on NeoGiga {$country} for engineers, procurement teams, schools, makers and B2B buyers who need reliable product identity, sourcing and RFQ support.";
        $long .= "\n\n";
        $long .= "NeoGiga keeps product identity separated from regional seller offers, warehouse availability and pricing overlays. ";
        $long .= "Use this page to review {$identifier}, brand, manufacturer, category and available specifications before requesting a quote or adding the item to a project BOM.";

        return [
            'meta_title' => $this->limit($title, 120),
            'meta_description' => $this->limit($description, 158),
            'short_description' => $this->limit($short, 420),
            'description' => $long,
            'specifications' => $this->productSpecifications($p, $brand, $manufacturer, $category),
            'seo_meta' => [
                'title' => $this->limit($title, 120),
                'description' => $this->limit($description, 158),
                'source' => 'neogiga_seo_generator',
                'country' => $country,
                'website_tag' => $websiteTag,
                'generated_at' => now()->toIso8601String(),
                'confidence_level' => 'generated_from_catalog_fields',
            ],
        ];
    }

    public function brand(object|array $brand, string $country = 'Global', string $websiteTag = 'Engineering Marketplace'): array
    {
        $b = (object) $brand;
        $name = $this->clean($b->name ?? 'Brand');
        $title = "{$name} Products on NeoGiga {$country} - {$websiteTag}";
        $description = "Browse {$name} products on NeoGiga {$country}. Find engineering parts, technical references, RFQ sourcing, regional availability and B2B procurement support.";

        return [
            'description' => "Explore {$name} product families, parts and sourcing options through NeoGiga {$country}. Brand pages connect catalog identity with RFQ, regional stock routing and seller offers when available.",
            'seo_meta' => [
                'title' => $this->limit($title, 120),
                'description' => $this->limit($description, 158),
                'source' => 'neogiga_seo_generator',
                'country' => $country,
                'website_tag' => $websiteTag,
                'generated_at' => now()->toIso8601String(),
                'confidence_level' => 'generated_from_brand_fields',
            ],
        ];
    }

    public function category(object|array $category, string $country = 'Global', string $websiteTag = 'Engineering Marketplace'): array
    {
        $c = (object) $category;
        $name = $this->clean($c->name ?? 'Category');
        $title = "{$name} Products on NeoGiga {$country} - {$websiteTag}";
        $description = "Shop {$name} on NeoGiga {$country}. Compare products, MPNs, brands, manufacturer data, RFQ sourcing and regional availability for engineering procurement.";

        return [
            'description' => "{$name} on NeoGiga {$country} helps engineers and procurement teams discover related products, product families, MPNs, brands and sourcing options in one catalog workflow.",
            'seo_meta' => [
                'title' => $this->limit($title, 120),
                'description' => $this->limit($description, 158),
                'source' => 'neogiga_seo_generator',
                'country' => $country,
                'website_tag' => $websiteTag,
                'generated_at' => now()->toIso8601String(),
                'confidence_level' => 'generated_from_category_fields',
            ],
        ];
    }

    public function manufacturer(object|array $manufacturer, string $country = 'Global', string $websiteTag = 'Engineering Marketplace'): array
    {
        $m = (object) $manufacturer;
        $name = $this->clean($m->name ?? 'Manufacturer');
        $title = "{$name} Manufacturer Parts on NeoGiga {$country} - {$websiteTag}";
        $description = "Find {$name} manufacturer parts, MPNs, brand links, RFQ sourcing and regional procurement support on NeoGiga {$country}.";

        return [
            'seo_title' => $this->limit($title, 120),
            'seo_description' => $this->limit($description, 158),
            'overview' => "{$name} manufacturer data on NeoGiga {$country} connects verified product identity, MPN search, brand relationships and sourcing workflows for engineering and B2B procurement teams.",
        ];
    }

    public function seller(object|array $seller, string $country = 'Global', string $websiteTag = 'Engineering Marketplace'): array
    {
        $s = (object) $seller;
        $name = $this->clean($s->name ?? 'Seller');
        $title = "{$name} Seller Profile on NeoGiga {$country} - {$websiteTag}";
        $description = "View {$name} seller profile on NeoGiga {$country}. Check verified status, product categories, RFQ support and marketplace procurement details.";

        return [
            'description' => "{$name} is listed in the NeoGiga {$country} seller ecosystem for engineering products, regional procurement, RFQ handling and marketplace sourcing workflows.",
            'metadata' => [
                'seo_title' => $this->limit($title, 120),
                'seo_description' => $this->limit($description, 158),
                'seo_source' => 'neogiga_seo_generator',
                'seo_country' => $country,
                'seo_website_tag' => $websiteTag,
                'seo_generated_at' => now()->toIso8601String(),
                'seo_confidence_level' => 'generated_from_seller_fields',
            ],
        ];
    }

    private function productSpecifications(object $p, string $brand, string $manufacturer, string $category): array
    {
        return collect([
            'Brand' => $brand,
            'Manufacturer' => $manufacturer,
            'Manufacturer Part Number' => $this->clean($p->mpn ?? ''),
            'NeoGiga SKU' => $this->clean($p->sku ?? ''),
            'Category' => $category,
            'GTIN' => $this->clean($p->gtin ?? ''),
            'HS Code' => $this->clean($p->hs_code ?? ''),
            'ECCN' => $this->clean($p->eccn ?? ''),
            'Lifecycle Status' => $this->clean($p->lifecycle_status ?? ''),
            'Country of Origin' => $this->clean($p->country_of_origin ?? ''),
        ])->filter(fn ($value) => $value !== '')->all();
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? '');
    }

    private function limit(string $value, int $limit): string
    {
        $clean = $this->clean($value);
        if (mb_strlen($clean) <= $limit) {
            return $clean;
        }

        $truncated = rtrim(mb_substr($clean, 0, $limit));
        $wordSafe = preg_replace('/\s+\S*$/u', '', $truncated);

        return trim($wordSafe ?: $truncated);
    }
}
