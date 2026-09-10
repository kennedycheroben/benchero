<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\MpesaService;

class MpesaCallbackController extends Controller
{
    private MpesaService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new MpesaService();
    }

    public function handle(Request $request): Response
    {
        $rawContent = file_get_contents('php://input');
        $data = json_decode($rawContent, true);

        if (!$data) {
            return Response::json(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON'], 400);
        }

        $processed = $this->service->processCallback($data);

        return Response::json([
            'ResultCode' => 0,
            'ResultDesc' => $processed ? 'Success' : 'Callback Processed'
        ]);
    }
}
