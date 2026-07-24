<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function generate(Invoice $invoice): string
    {
        $invoice->load(['items', 'marketplace', 'vendor', 'user']);

        $verificationUrl = $this->invoiceService->getVerificationUrl($invoice);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'items' => $invoice->items,
            'marketplace' => $invoice->marketplace,
            'vendor' => $invoice->vendor,
            'verificationUrl' => $verificationUrl,
            'qrData' => $this->buildQrData($invoice),
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('isFontSubsettingEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);

        $filename = "invoices/{$invoice->invoice_number}.pdf";
        $directory = dirname($filename);

        if (! Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $path = storage_path("app/{$filename}");
        file_put_contents($path, $pdf->output());

        $invoice->update([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
        ]);

        return $filename;
    }

    public function stream(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $invoice->load(['items', 'marketplace', 'vendor', 'user']);

        $verificationUrl = $this->invoiceService->getVerificationUrl($invoice);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'items' => $invoice->items,
            'marketplace' => $invoice->marketplace,
            'vendor' => $invoice->vendor,
            'verificationUrl' => $verificationUrl,
            'qrData' => $this->buildQrData($invoice),
        ]);

        $pdf->setPaper('a4');

        return $pdf->stream("{$invoice->invoice_number}.pdf");
    }

    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $invoice->load(['items', 'marketplace', 'vendor', 'user']);

        $verificationUrl = $this->invoiceService->getVerificationUrl($invoice);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'items' => $invoice->items,
            'marketplace' => $invoice->marketplace,
            'vendor' => $invoice->vendor,
            'verificationUrl' => $verificationUrl,
            'qrData' => $this->buildQrData($invoice),
        ]);

        $pdf->setPaper('a4');

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }

    private function buildQrData(Invoice $invoice): string
    {
        return $this->invoiceService->getVerificationUrl($invoice);
    }
}
