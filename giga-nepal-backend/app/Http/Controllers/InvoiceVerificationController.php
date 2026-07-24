<?php

namespace App\Http\Controllers;

use App\Services\Invoice\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceVerificationController extends Controller
{
    public function show(Request $request, string $invoiceNumber): View
    {
        $token = $request->input('token', '');
        $service = app(InvoiceService::class);
        $result = $service->verify($invoiceNumber, $token);

        return view('invoices.verify', $result + [
            'invoiceNumber' => $invoiceNumber,
        ]);
    }
}
