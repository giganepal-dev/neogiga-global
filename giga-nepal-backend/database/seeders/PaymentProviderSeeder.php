<?php

namespace Database\Seeders;

use App\Models\Payments\PaymentProvider;
use Illuminate\Database\Seeder;

/**
 * Registers the supported payment providers as DISABLED sandbox entries.
 * No credentials are stored — real keys live in .env and each provider stays
 * off until explicitly enabled + wired. Idempotent. Run explicitly:
 *   php artisan db:seed --class=Database\\Seeders\\PaymentProviderSeeder --force
 */
class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['code' => 'cod', 'name' => 'Cash on Delivery', 'currencies' => null, 'sort' => 10],
            ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'currencies' => null, 'sort' => 20],
            ['code' => 'wallet', 'name' => 'Store Credit (Wallet)', 'currencies' => null, 'sort' => 30],
            ['code' => 'esewa', 'name' => 'eSewa', 'currencies' => ['NPR'], 'sort' => 40],
            ['code' => 'khalti', 'name' => 'Khalti', 'currencies' => ['NPR'], 'sort' => 50],
            ['code' => 'fonepay', 'name' => 'Fonepay', 'currencies' => ['NPR'], 'sort' => 60],
            ['code' => 'stripe', 'name' => 'Stripe', 'currencies' => ['USD', 'EUR', 'GBP', 'INR'], 'sort' => 70],
            ['code' => 'paypal', 'name' => 'PayPal', 'currencies' => ['USD', 'EUR', 'GBP'], 'sort' => 80],
        ];

        foreach ($providers as $p) {
            PaymentProvider::updateOrCreate(
                ['code' => $p['code']],
                [
                    'name' => $p['name'],
                    'is_enabled' => false,   // off until explicitly enabled
                    'is_live' => false,      // sandbox
                    'supported_currencies' => $p['currencies'],
                    'config' => [],          // no secrets — credentials live in .env
                    'sort_order' => $p['sort'],
                ],
            );
        }
    }
}
