<?php

namespace Benchero\Controllers;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;

class HealthController extends Controller
{
    public function check(Request $request): Response
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query('SELECT 1');
            $stmt->fetch();
            return $this->json(['status' => 'ok']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error'], 503);
        }
    }
}
