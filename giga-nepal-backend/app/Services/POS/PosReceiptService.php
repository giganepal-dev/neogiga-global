<?php

namespace App\Services\POS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PosReceiptService
{
    public function issueToken(int $saleId): string
    {
        $token = Str::lower(Str::random(32));
        if (Schema::hasTable('pos_sales') && Schema::hasColumn('pos_sales', 'receipt_qr_token')) {
            DB::table('pos_sales')->where('id', $saleId)->update([
                'receipt_qr_token' => $token,
                'updated_at' => now(),
            ]);
        }

        return $token;
    }

    public function receiptUrl(string $token): string
    {
        $path = trim((string) config('pos.receipt_url_path', '/pos/receipt'), '/');

        return url('/'.$path.'/'.$token);
    }

    public function saleByToken(string $token): ?object
    {
        if (! Schema::hasTable('pos_sales') || ! Schema::hasColumn('pos_sales', 'receipt_qr_token')) {
            return null;
        }

        $sale = DB::table('pos_sales')->where('receipt_qr_token', $token)->first();
        if (! $sale) {
            return null;
        }

        $sale->items = DB::table('pos_sale_items')->where('pos_sale_id', $sale->id)->get();
        $sale->payments = DB::table('pos_payments')->where('pos_sale_id', $sale->id)->get();

        return $sale;
    }
}
