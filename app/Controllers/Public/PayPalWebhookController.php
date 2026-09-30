<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\PaymentService;

class PayPalWebhookController extends Controller
{
    private PaymentService $paymentService;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new PaymentService();
    }

    public function handle(Request $request): Response
    {
        $rawContent = file_get_contents('php://input');
        $data = json_decode($rawContent, true);
        if (!$data && !empty($request->body())) {
            $data = $request->body();
        }

        if (!$data || !is_array($data)) {
            return Response::json(['status' => 'error', 'message' => 'Invalid JSON payload'], 400);
        }

        // Collect PayPal signature verification headers
        $headers = [];
        $headerKeys = [
            'PAYPAL-TRANSMISSION-ID' => 'paypal-transmission-id',
            'PAYPAL-TRANSMISSION-TIME' => 'paypal-transmission-time',
            'PAYPAL-CERT-URL' => 'paypal-cert-url',
            'PAYPAL-AUTH-ALGO' => 'paypal-auth-algo',
            'PAYPAL-TRANSMISSION-SIG' => 'paypal-transmission-sig'
        ];

        foreach ($headerKeys as $key => $altKey) {
            $val = $request->header($key) ?: ($request->header($altKey) ?: ($_SERVER['HTTP_' . str_replace('-', '_', $key)] 
                ?? $_SERVER['HTTP_' . str_replace('-', '_', $altKey)] 
                ?? null));
            if ($val !== null && $val !== '') {
                $headers[$key] = $val;
                $headers[$altKey] = $val;
            }
        }

        $processed = $this->paymentService->processWebhook('paypal', $data, $headers);

        return Response::json([
            'status' => 'success',
            'processed' => (bool)$processed
        ], 200);
    }
}
