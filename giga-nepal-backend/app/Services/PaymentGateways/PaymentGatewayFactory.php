<?php

namespace App\Services\PaymentGateways;

use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Services\PaymentGateways\EsewaGateway;
use App\Services\PaymentGateways\KhaltiGateway;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentGateways\CashOnDeliveryGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Get payment gateway instance
     *
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public static function get(string $gateway): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'esewa' => new EsewaGateway(),
            'khalti' => new KhaltiGateway(),
            'stripe' => new StripeGateway(),
            'cod', 'cash_on_delivery' => new CashOnDeliveryGateway(),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Get list of available gateways
     *
     * @return array
     */
    public static function getAvailableGateways(): array
    {
        return [
            'esewa' => 'eSewa',
            'khalti' => 'Khalti',
            'stripe' => 'Stripe',
            'cod' => 'Cash on Delivery',
        ];
    }

    /**
     * Check if gateway is available
     *
     * @param string $gateway
     * @return bool
     */
    public static function isAvailable(string $gateway): bool
    {
        return in_array(strtolower($gateway), array_keys(self::getAvailableGateways()));
    }
}
