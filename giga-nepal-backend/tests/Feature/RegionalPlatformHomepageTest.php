<?php

namespace Tests\Feature;

use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceDomain;
use App\Models\Marketplace\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegionalPlatformHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_nepal_and_india_use_one_frontend_with_separate_seo(): void
    {
        $globalMarketplace = $this->marketplace('GLOBAL', 'NeoGiga Global', 'GL', 'USD', 'neogiga.com', 'NeoGiga Global Engineering Marketplace');
        $nepalMarketplace = $this->marketplace('NEPAL', 'GigaNepal', 'NP', 'NPR', 'giganepal.com', 'GigaNepal Engineering Marketplace');
        $indiaMarketplace = $this->marketplace('INDIA', 'NeoGiga India', 'IN', 'INR', 'neogiga.in', 'NeoGiga India Engineering Marketplace');
        MarketplaceDomain::create(['marketplace_id' => $nepalMarketplace->id, 'domain' => 'np.neogiga.com', 'is_primary' => false, 'is_active' => true]);
        MarketplaceDomain::create(['marketplace_id' => $indiaMarketplace->id, 'domain' => 'in.neogiga.com', 'is_primary' => false, 'is_active' => true]);
        $product = Product::create([
            'name' => 'Regional Price Integrity Test',
            'slug' => 'regional-price-integrity-test',
            'sku' => 'NG-REGIONAL-PRICE-TEST',
            'status' => 'approved',
            'visibility_status' => 'public',
            'base_price' => '1.0500',
            'cost_price' => '1.0000',
            'sale_price' => '1.0500',
            'stock_quantity' => 10_000,
            'track_inventory' => true,
        ]);
        DB::table('marketplace_product_prices')->insert([
            'product_id' => $product->id,
            'marketplace_id' => $globalMarketplace->id,
            'base_price' => '1.0500',
            'sale_price' => '1.0500',
            'cost_price' => '1.0000',
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::flush();

        $global = $this->regionalGet('neogiga.com');
        $nepal = $this->regionalGet('np.neogiga.com');
        $india = $this->regionalGet('in.neogiga.com');

        $global->assertOk()
            ->assertSee('<title>NeoGiga Global Engineering Marketplace</title>', false)
            ->assertSee('<link rel="canonical" href="https://neogiga.com/en">', false)
            ->assertSee('one global platform.', false)
            ->assertDontSee('India Edition · Preview');
        $nepal->assertOk()
            ->assertSee('<title>GigaNepal Engineering Marketplace</title>', false)
            ->assertSee('<link rel="canonical" href="https://np.neogiga.com/en">', false)
            ->assertSee('GigaNepal')
            ->assertSee('NPR')
            ->assertSee('1.05 USD')
            ->assertDontSee('1.05 NPR');
        $india->assertOk()
            ->assertSee('<title>NeoGiga India Engineering Marketplace</title>', false)
            ->assertSee('<link rel="canonical" href="https://in.neogiga.com/en">', false)
            ->assertSee('NeoGiga India')
            ->assertSee('INR');

        foreach ([$global, $nepal, $india] as $response) {
            $response->assertSee('/images/brand/neogiga-icon-192.png', false)
                ->assertSee('/en/products', false)
                ->assertSee('/en/categories', false)
                ->assertSee('/en/rfq', false)
                ->assertSee('Shared NeoGiga platform');
        }

        $global->assertSee('hreflang="en-np" href="https://np.neogiga.com/en"', false)
            ->assertSee('hreflang="en-in" href="https://in.neogiga.com/en"', false);

        $this->regionalGet('www.giganepal.com')
            ->assertOk()
            ->assertSee('<title>GigaNepal Engineering Marketplace</title>', false)
            ->assertSee('<link rel="canonical" href="https://np.neogiga.com/en">', false);
        $this->regionalGet('np.neogiga.com')
            ->assertOk()
            ->assertSee('<title>GigaNepal Engineering Marketplace</title>', false)
            ->assertSee('<link rel="canonical" href="https://np.neogiga.com/en">', false);
    }

    public function test_regional_hosts_permanently_redirect_marketplace_prefix_aliases_to_their_en_tree(): void
    {
        $this->marketplace('GLOBAL', 'NeoGiga Global', 'GL', 'USD', 'neogiga.com', 'NeoGiga Global Engineering Marketplace');
        $nepal = $this->marketplace('NEPAL', 'GigaNepal', 'NP', 'NPR', 'giganepal.com', 'GigaNepal Engineering Marketplace');
        $this->marketplace('INDIA', 'NeoGiga India', 'IN', 'INR', 'neogiga.in', 'NeoGiga India Engineering Marketplace');
        MarketplaceDomain::create(['marketplace_id' => $nepal->id, 'domain' => 'np.neogiga.com', 'is_primary' => false, 'is_active' => true]);
        Cache::flush();

        $this->get('https://giganepal.com/in')
            ->assertStatus(301)
            ->assertRedirect('https://np.neogiga.com/en');
        $this->get('https://giganepal.com/np/products?q=esp32&sort=price')
            ->assertStatus(301)
            ->assertRedirect('https://np.neogiga.com/en/products?q=esp32&sort=price');
        $this->get('https://np.neogiga.com/bd/categories/sensors')
            ->assertStatus(301)
            ->assertRedirect('https://np.neogiga.com/en/categories/sensors');
        $this->get('https://giganepal.com/lk')
            ->assertStatus(301)
            ->assertRedirect('https://np.neogiga.com/en');
        $this->get('https://neogiga.in/np/products')
            ->assertStatus(301)
            ->assertRedirect('https://in.neogiga.com/en/products');

        $this->get('https://giganepal.com/en')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://np.neogiga.com/en">', false);
        $this->get('https://neogiga.com/np')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://np.neogiga.com/en">', false);
    }

    private function regionalGet(string $host)
    {
        return $this->get("https://{$host}/en");
    }

    private function marketplace(
        string $code,
        string $name,
        string $countryCode,
        string $currencyCode,
        string $domain,
        string $seoTitle,
    ): Marketplace {
        $country = Country::firstOrCreate(['iso_code_2' => $countryCode], [
            'name' => $countryCode === 'GL' ? 'Global' : ($countryCode === 'NP' ? 'Nepal' : 'India'),
            'iso_code_3' => $countryCode.'X',
            'currency_code' => $currencyCode,
            'is_active' => true,
        ]);
        $currency = Currency::firstOrCreate(['code' => $currencyCode], [
            'name' => $currencyCode,
            'symbol' => $currencyCode,
            'native_symbol' => $currencyCode,
            'decimal_places' => 2,
            'is_active' => true,
            'exchange_rate' => 1,
        ]);
        $marketplace = Marketplace::create([
            'name' => $name,
            'regional_brand_name' => $name,
            'code' => $code,
            'url_prefix' => match ($code) {
                'NEPAL' => 'np',
                'INDIA' => 'in',
                default => null,
            },
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'timezone' => 'UTC',
            'locale' => 'en',
            'domain' => $domain,
            'canonical_domain' => $domain,
            'is_active' => true,
            'is_visible' => true,
            'indexable' => true,
            'hreflang_enabled' => true,
            'launch_status' => 'active',
            'seo_title' => $seoTitle,
            'seo_description' => $name.' regional engineering catalog.',
            'seo_canonical_url' => 'https://'.$domain.'/en',
            'seo_robots' => 'index,follow',
        ]);

        MarketplaceDomain::create([
            'marketplace_id' => $marketplace->id,
            'domain' => $domain,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $marketplace;
    }
}
