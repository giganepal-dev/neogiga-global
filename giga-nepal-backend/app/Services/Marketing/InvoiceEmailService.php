<?php

namespace App\Services\Marketing;

class InvoiceEmailService
{
    public function __construct(private TransactionalEmailService $email) {}

    public function sendLink(string $email, string $invoiceUrl): int
    {
        return $this->email->queue($email, 'Your NeoGiga invoice', "Invoice: {$invoiceUrl}", ['invoice_url' => $invoiceUrl]);
    }
}
