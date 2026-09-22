<?php

namespace Benchero\Services\Gateways;

use Benchero\Contracts\PaymentGatewayInterface;
use Exception;

class PaymentGatewayFactory
{
    /**
     * Resolve payment gateway instance by provider name or default configuration.
     */
    public static function create(?string $providerName = null): PaymentGatewayInterface
    {
        $provider = strtolower($providerName ?: env('PAYMENT_DEFAULT_PROVIDER', env('PAYMENT_PROVIDER', 'imbank')));

        return match ($provider) {
            'paypal' => new PayPalPaymentGateway(),
            'imbank', 'mpesa' => new ImBankPaymentGateway(),
            default => new ImBankPaymentGateway(),
        };
    }

    /**
     * Resolve payment gateway instance based on requested payment method.
     */
    public static function getProviderForMethod(string $method): PaymentGatewayInterface
    {
        $normalized = strtolower(trim($method));

        return match ($normalized) {
            'paypal', 'card', 'credit_card', 'debit_card' => self::create(env('PAYMENT_INTERNATIONAL_PROVIDER', 'paypal')),
            'mpesa', 'stk', 'mobile_money', 'imbank' => self::create(env('PAYMENT_KENYA_PROVIDER', 'imbank')),
            default => self::create(),
        };
    }
}
