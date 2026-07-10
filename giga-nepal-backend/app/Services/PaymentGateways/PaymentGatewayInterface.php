<?php

namespace App\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment transaction
     *
     * @param array $data
     * @return array
     */
    public function initiate(array $data): array;

    /**
     * Verify payment status from gateway
     *
     * @param string $transactionId
     * @return array
     */
    public function verify(string $transactionId): array;

    /**
     * Process refund
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refund(string $transactionId, float $amount): array;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;
}
