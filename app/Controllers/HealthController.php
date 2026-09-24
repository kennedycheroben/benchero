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

            $sportsHealth = [
                'status' => 'unknown',
                'last_successful_sync' => null,
                'recent_error' => null,
                'scheduler_status' => 'inactive'
            ];

            try {
                $stmtSuccess = $pdo->query("
                    SELECT created_at
                    FROM sports_sync_logs
                    WHERE status = 'success'
                    ORDER BY id DESC LIMIT 1
                ");
                $lastSuccess = $stmtSuccess->fetchColumn() ?: null;
                $sportsHealth['last_successful_sync'] = $lastSuccess;

                $stmtError = $pdo->query("
                    SELECT error_message, created_at
                    FROM sports_sync_logs
                    WHERE status = 'error'
                    ORDER BY id DESC LIMIT 1
                ");
                $recentErrRow = $stmtError->fetch(\PDO::FETCH_ASSOC);
                if ($recentErrRow) {
                    $cleanErr = preg_replace('/(key|token|secret|password)=([^\s&]+)/i', '$1=REDACTED', $recentErrRow['error_message'] ?? '');
                    $sportsHealth['recent_error'] = [
                        'message' => $cleanErr,
                        'occurred_at' => $recentErrRow['created_at']
                    ];
                }

                if ($lastSuccess) {
                    $secondsSince = abs(time() - strtotime($lastSuccess));
                    if ($secondsSince < 1800) { // Under 30 minutes
                        $sportsHealth['status'] = 'healthy';
                        $sportsHealth['scheduler_status'] = 'active';
                    } elseif ($secondsSince < 7200) { // Under 2 hours
                        $sportsHealth['status'] = 'stale';
                        $sportsHealth['scheduler_status'] = 'delayed';
                    } else {
                        $sportsHealth['status'] = 'stale';
                        $sportsHealth['scheduler_status'] = 'inactive';
                    }
                } else {
                    $sportsHealth['status'] = 'uninitialized';
                    $sportsHealth['scheduler_status'] = 'inactive';
                }
            } catch (\Throwable) {
                $sportsHealth['status'] = 'unreachable';
            }

            return $this->json([
                'status' => 'ok',
                'database' => 'connected',
                'sports_sync' => $sportsHealth
            ]);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error'], 503);
        }
    }
}
