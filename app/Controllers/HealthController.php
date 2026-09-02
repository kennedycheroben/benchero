<?php

namespace Teamora\Controllers;

use Teamora\Core\Controller;
use Teamora\Core\Database\Database;
use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;

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
