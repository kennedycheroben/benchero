<?php

namespace Benchero\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Get the identifier for the payment provider.
     */
    public function getProviderName(): string;

    /**
     * Initiate STK Push / Payment request with the gateway provider.
     *
     * @param array $intent Payment intent array details
     * @return array Normalized response array ['success' => bool, 'provider_reference' => ?string, 'message' => string, 'raw' => array]
     */
    public function initiatePayment(array $intent): array;

    /**
     * Query status of a payment by provider reference (if supported).
     *
     * @param string $providerReference
     * @return array Normalized status response
     */
    public function queryPaymentStatus(string $providerReference): array;

    /**
     * Verify and parse server-to-server callback / notification payload.
     *
     * @param array $payload Incoming request body payload
     * @param array $headers Incoming request headers
     * @return array Normalized result ['valid' => bool, 'status' => string, 'provider_reference' => ?string, 'receipt' => ?string, 'amount' => ?float, 'currency' => ?string, 'phone' => ?string, 'message' => string]
     */
    public function verifyPaymentNotification(array $payload, array $headers = []): array;

    /**
     * Reconcile payment against provider transaction records.
     *
     * @param string $reference
     * @return array
     */
    public function reconcilePayment(string $reference): array;

    /**
     * Get list of currencies supported by this payment provider.
     *
     * @return array
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get list of payment methods supported by this payment provider (e.g., 'mpesa', 'paypal', 'card').
     *
     * @return array
     */
    public function getSupportedPaymentMethods(): array;
}
