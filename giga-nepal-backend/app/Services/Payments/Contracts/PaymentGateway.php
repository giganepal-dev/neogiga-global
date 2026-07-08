<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payments\PaymentProvider;

/**
 * Gateway adapter contract. Concrete adapters (eSewa, Khalti, FonePay, Stripe,
 * PayPal, bank transfer, COD, wallet) implement this. No adapter performs a live
 * network call or reads credentials until wired against .env secrets in a later,
 * reviewed step. All amounts passed in are trusted server-side values.
 */
interface PaymentGateway
{
    public function code(): string;

    public function provider(): PaymentProvider;

    /**
     * Begin a payment. Returns a normalized result. Placeholder adapters return
     * ['status' => 'unconfigured'] so the caller can fall back to manual flow.
     *
     * @return array{status:string, redirect_url?:string, reference?:string, message?:string}
     */
    public function initiate(int $orderId, float $amount, string $currency, array $context = []): array;

    /**
     * Verify a gateway callback/webhook. Signature validation is a placeholder
     * until real credentials are configured.
     *
     * @return array{verified:bool, status:string, reference?:string}
     */
    public function verify(array $payload): array;

    /**
     * @return array{status:string, message?:string}
     */
    public function refund(int $paymentId, float $amount, array $context = []): array;
}
