<?php

namespace Benchero\Services\Gateways;

use Benchero\Contracts\PaymentGatewayInterface;

class PayPalPaymentGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $webhookId;
    private string $environment;
    private string $defaultCurrency;
    private array $supportedCurrencies;

    public function __construct()
    {
        $this->clientId = env('PAYPAL_CLIENT_ID', '');
        $this->clientSecret = env('PAYPAL_CLIENT_SECRET', '');
        $this->webhookId = env('PAYPAL_WEBHOOK_ID', '');
        $this->environment = strtolower(env('PAYPAL_ENVIRONMENT', 'sandbox'));
        $this->defaultCurrency = strtoupper(env('PAYPAL_CURRENCY', 'USD'));

        $supported = env('PAYPAL_SUPPORTED_CURRENCIES', 'USD,EUR,GBP');
        $this->supportedCurrencies = array_map('trim', explode(',', strtoupper($supported)));
        if (!in_array($this->defaultCurrency, $this->supportedCurrencies, true)) {
            $this->supportedCurrencies[] = $this->defaultCurrency;
        }
    }

    public function getProviderName(): string
    {
        return 'paypal';
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function getSupportedPaymentMethods(): array
    {
        return ['paypal', 'card'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    private function getBaseUrl(): string
    {
        return ($this->environment === 'production')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Create a PayPal v2 Checkout Order with capture intent.
     */
    public function initiatePayment(array $intent): array
    {
        $amount = (float)($intent['amount'] ?? 0);
        if ($amount <= 0) {
            return [
                'success' => false,
                'provider_reference' => null,
                'message' => 'Payment amount must be greater than zero.',
                'raw' => []
            ];
        }

        $currency = strtoupper($intent['currency'] ?? $this->defaultCurrency);
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            return [
                'success' => false,
                'provider_reference' => null,
                'message' => "Currency {$currency} is not supported for PayPal checkout. Supported: " . implode(', ', $this->supportedCurrencies),
                'raw' => []
            ];
        }

        $reference = $intent['reference'] ?? ('CHK-' . strtoupper(substr(md5(uniqid('', true)), 0, 12)));
        $customId = $intent['payment_intent_id'] ?? $intent['intent_id'] ?? $reference;
        $returnUrl = $intent['return_url'] ?? (env('APP_URL') . '/billing/paypal/return');
        $cancelUrl = $intent['cancel_url'] ?? (env('APP_URL') . '/billing/paypal/cancel');

        $isTestAccount = is_test_account() || is_test_account($intent['organization_id'] ?? null);
        // Test environment simulation (strictly testing environment, non-production unconfigured, or test account when unconfigured)
        $isTestMode = (env('APP_ENV') === 'testing' || (env('APP_ENV') !== 'production' && !$this->isConfigured()) || ($isTestAccount && !$this->isConfigured()));

        if ($isTestMode) {
            $simulatedOrderId = 'PAYPAL_SIM_' . strtoupper(substr(md5(uniqid($reference, true)), 0, 14));
            return [
                'success' => true,
                'provider_reference' => $simulatedOrderId,
                'order_id' => $simulatedOrderId,
                'approval_url' => "https://www.sandbox.paypal.com/checkoutnow?token={$simulatedOrderId}",
                'message' => 'PayPal order initialized (test simulation mode).',
                'is_simulation' => true,
                'raw' => [
                    'id' => $simulatedOrderId,
                    'status' => 'CREATED',
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $reference,
                            'amount' => ['currency_code' => $currency, 'value' => number_format($amount, 2, '.', '')]
                        ]
                    ]
                ]
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'provider_reference' => null,
                'message' => 'PayPal payment gateway is not properly configured. Please contact support.',
                'raw' => []
            ];
        }

        // Live PayPal REST API request
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return [
                    'success' => false,
                    'provider_reference' => null,
                    'message' => 'Unable to authenticate with PayPal. Please try again shortly.',
                    'raw' => []
                ];
            }

            $orderPayload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $reference,
                        'custom_id' => $customId,
                        'description' => 'Benchero Subscription (' . ($intent['plan_name'] ?? 'Pro Plan') . ')',
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ]
                    ]
                ],
                'application_context' => [
                    'brand_name' => env('BRAND_NAME', 'Benchero'),
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl
                ]
            ];

            $ch = curl_init($this->getBaseUrl() . '/v2/checkout/orders');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Prefer: return=representation'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderPayload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $responseStr = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($responseStr, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
                $approvalUrl = null;
                foreach ($data['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'approve') {
                        $approvalUrl = $link['href'] ?? null;
                        break;
                    }
                }

                return [
                    'success' => true,
                    'provider_reference' => (string)$data['id'],
                    'order_id' => (string)$data['id'],
                    'approval_url' => $approvalUrl,
                    'message' => 'PayPal order created successfully.',
                    'raw' => $data
                ];
            }

            $errorMsg = $data['message'] ?? $data['details'][0]['description'] ?? 'PayPal order creation failed.';
            return [
                'success' => false,
                'provider_reference' => null,
                'message' => $errorMsg,
                'raw' => $data
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'provider_reference' => null,
                'message' => 'Unable to communicate with PayPal gateway. Please try again.',
                'raw' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Capture an approved PayPal order server-side.
     */
    public function capturePayment(string $orderId): array
    {
        if (str_starts_with($orderId, 'PAYPAL_SIM_') || env('APP_ENV') === 'testing' || (env('APP_ENV') !== 'production' && !$this->isConfigured())) {
            $captureId = 'CAP_SIM_' . strtoupper(substr(md5(uniqid($orderId, true)), 0, 14));
            return [
                'success' => true,
                'status' => 'completed',
                'order_id' => $orderId,
                'capture_id' => $captureId,
                'provider_reference' => $orderId,
                'receipt' => $captureId,
                'payer_email' => 'paypal_test_payer@example.com',
                'payer_id' => 'PAYER_SIM_123',
                'amount' => 20.00,
                'currency' => $this->defaultCurrency,
                'message' => 'Simulated PayPal payment captured successfully.'
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'PayPal gateway is not configured.'
            ];
        }

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Failed to obtain PayPal authentication token.'
                ];
            }

            $ch = curl_init($this->getBaseUrl() . "/v2/checkout/orders/{$orderId}/capture");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'Prefer: return=representation'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $responseStr = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($responseStr, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300) {
                $status = strtoupper($data['status'] ?? '');
                $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? [];
                $captureId = $capture['id'] ?? null;
                $captureStatus = strtoupper($capture['status'] ?? $status);

                $payer = $data['payer'] ?? [];
                $payerEmail = $payer['email_address'] ?? null;
                $payerId = $payer['payer_id'] ?? null;

                $amount = isset($capture['amount']['value']) ? (float)$capture['amount']['value'] : null;
                $currency = $capture['amount']['currency_code'] ?? null;

                $isCompleted = ($captureStatus === 'COMPLETED' || $status === 'COMPLETED');

                return [
                    'success' => $isCompleted,
                    'status' => $isCompleted ? 'completed' : strtolower($captureStatus),
                    'order_id' => (string)$data['id'],
                    'capture_id' => $captureId,
                    'provider_reference' => (string)$data['id'],
                    'receipt' => $captureId ?: (string)$data['id'],
                    'payer_email' => $payerEmail,
                    'payer_id' => $payerId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'message' => $isCompleted ? 'Payment captured successfully.' : "Capture status: {$captureStatus}",
                    'raw' => $data
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $data['message'] ?? 'Failed to capture PayPal payment.',
                'raw' => $data
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Network error during PayPal payment capture.',
                'raw' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Query status of an order via GET /v2/checkout/orders/{id}.
     */
    public function queryPaymentStatus(string $providerReference): array
    {
        if (str_starts_with($providerReference, 'PAYPAL_SIM_') || (env('APP_ENV') === 'testing' && !$this->isConfigured())) {
            return [
                'success' => true,
                'status' => 'completed',
                'provider_reference' => $providerReference,
                'message' => 'Simulated PayPal status query.'
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'unknown',
                'provider_reference' => $providerReference,
                'message' => 'PayPal gateway not configured.'
            ];
        }

        try {
            $token = $this->getAccessToken();
            $ch = curl_init($this->getBaseUrl() . "/v2/checkout/orders/{$providerReference}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $responseStr = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($responseStr, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300 && !empty($data['status'])) {
                $statusMap = [
                    'COMPLETED' => 'completed',
                    'APPROVED' => 'approved',
                    'CREATED' => 'initiated',
                    'SAVED' => 'pending',
                    'VOIDED' => 'cancelled',
                    'PAYER_ACTION_REQUIRED' => 'pending'
                ];
                $normalized = $statusMap[strtoupper($data['status'])] ?? 'pending';

                return [
                    'success' => true,
                    'status' => $normalized,
                    'provider_reference' => (string)$data['id'],
                    'message' => "Order status: {$data['status']}",
                    'raw' => $data
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'provider_reference' => $providerReference,
                'message' => $data['message'] ?? 'Could not retrieve PayPal order status.'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'provider_reference' => $providerReference,
                'message' => 'Error querying PayPal order status.'
            ];
        }
    }

    /**
     * Verify and parse server-to-server PayPal webhook notification.
     */
    public function verifyPaymentNotification(array $payload, array $headers = []): array
    {
        // 1. Validate payload structure
        $eventType = $payload['event_type'] ?? null;
        $resource = $payload['resource'] ?? [];

        if (!$eventType || empty($resource)) {
            return [
                'valid' => false,
                'status' => 'failed',
                'message' => 'Malformed PayPal webhook payload.'
            ];
        }

        // 2. Webhook signature verification if webhookId is configured
        if (!empty($this->webhookId) && $this->isConfigured()) {
            $transmissionId = $headers['paypal-transmission-id'] ?? $headers['PAYPAL-TRANSMISSION-ID'] ?? null;
            $transmissionTime = $headers['paypal-transmission-time'] ?? $headers['PAYPAL-TRANSMISSION-TIME'] ?? null;
            $certUrl = $headers['paypal-cert-url'] ?? $headers['PAYPAL-CERT-URL'] ?? null;
            $authAlgo = $headers['paypal-auth-algo'] ?? $headers['PAYPAL-AUTH-ALGO'] ?? null;
            $transmissionSig = $headers['paypal-transmission-sig'] ?? $headers['PAYPAL-TRANSMISSION-SIG'] ?? null;

            if ($transmissionId && $transmissionSig && $certUrl) {
                $verificationOk = $this->verifySignatureWithPayPal([
                    'transmission_id' => $transmissionId,
                    'transmission_time' => $transmissionTime,
                    'cert_url' => $certUrl,
                    'auth_algo' => $authAlgo,
                    'transmission_sig' => $transmissionSig,
                    'webhook_id' => $this->webhookId,
                    'webhook_event' => $payload
                ]);

                if (!$verificationOk) {
                    return [
                        'valid' => false,
                        'status' => 'failed',
                        'message' => 'PayPal webhook signature verification failed.'
                    ];
                }
            }
        }

        // 3. Process supported event types
        $status = 'pending';
        $orderId = null;
        $receipt = null;
        $amount = null;
        $currency = null;
        $payerEmail = null;

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $status = 'completed';
            $receipt = $resource['id'] ?? null;
            $amount = isset($resource['amount']['value']) ? (float)$resource['amount']['value'] : null;
            $currency = $resource['amount']['currency_code'] ?? null;

            // Link back to Order ID from supplementary_data or links
            $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
            if (!$orderId) {
                // Find order link in links
                foreach ($resource['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'up') {
                        $parts = explode('/', rtrim($link['href'] ?? '', '/'));
                        $orderId = end($parts);
                        break;
                    }
                }
            }
            if (!$orderId) {
                $orderId = $resource['custom_id'] ?? $receipt;
            }
        } elseif ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            $status = 'approved';
            $orderId = $resource['id'] ?? null;
            $amount = isset($resource['purchase_units'][0]['amount']['value']) ? (float)$resource['purchase_units'][0]['amount']['value'] : null;
            $currency = $resource['purchase_units'][0]['amount']['currency_code'] ?? null;
            $payerEmail = $resource['payer']['email_address'] ?? null;
        } elseif ($eventType === 'PAYMENT.CAPTURE.DENIED' || $eventType === 'PAYMENT.CAPTURE.DECLINED') {
            $status = 'failed';
            $receipt = $resource['id'] ?? null;
            $orderId = $resource['custom_id'] ?? $receipt;
        } else {
            // Other events acknowledged but not processed as payment finalization
            return [
                'valid' => true,
                'status' => 'ignored',
                'provider_reference' => $resource['id'] ?? null,
                'receipt' => null,
                'amount' => null,
                'currency' => null,
                'phone' => null,
                'message' => "Event {$eventType} acknowledged."
            ];
        }

        return [
            'valid' => true,
            'status' => $status,
            'provider_reference' => $orderId ?: $receipt,
            'receipt' => $receipt ?: $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'phone' => null,
            'payer_email' => $payerEmail,
            'message' => "Webhook event {$eventType} processed successfully."
        ];
    }

    public function reconcilePayment(string $reference): array
    {
        return $this->queryPaymentStatus($reference);
    }

    /**
     * Verify signature using PayPal's server-to-server verification endpoint.
     */
    private function verifySignatureWithPayPal(array $data): bool
    {
        if (env('APP_ENV') === 'testing') {
            return true;
        }

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return false;
            }

            $ch = curl_init($this->getBaseUrl() . '/v1/notifications/verify-webhook-signature');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $resStr = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($resStr, true) ?: [];
            return ($res['verification_status'] ?? '') === 'SUCCESS';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get OAuth2 Bearer token from PayPal.
     */
    private function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        static $cachedToken = null;
        static $expiresAt = 0;

        if ($cachedToken && time() < $expiresAt - 60) {
            return $cachedToken;
        }

        try {
            $ch = curl_init($this->getBaseUrl() . '/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en_US'
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $responseStr = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($responseStr, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300 && !empty($data['access_token'])) {
                $cachedToken = (string)$data['access_token'];
                $expiresIn = (int)($data['expires_in'] ?? 3600);
                $expiresAt = time() + $expiresIn;
                return $cachedToken;
            }
        } catch (\Throwable $e) {
            // Silently fail without exposing secrets
        }

        return null;
    }
}
