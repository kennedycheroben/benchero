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
        $provider = strtolower($providerName ?: env('PAYMENT_PROVIDER', 'imbank'));

        switch ($provider) {
            case 'imbank':
            case 'mpesa':
            default:
                return new ImBankPaymentGateway();
        }
    }
}
