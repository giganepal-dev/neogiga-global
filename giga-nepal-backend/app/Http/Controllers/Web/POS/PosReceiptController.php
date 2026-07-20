<?php

namespace App\Http\Controllers\Web\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\PosReceiptService;
use Illuminate\View\View;

class PosReceiptController extends Controller
{
    public function show(string $token, PosReceiptService $receipts): View
    {
        $sale = $receipts->saleByToken($token);
        abort_unless($sale, 404);

        return view('pos.receipt', compact('sale', 'token'));
    }
}
