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
            $dbHealthy = true;
        } catch (\Throwable) {
            $dbHealthy = false;
        }

        if (!$dbHealthy) {
            return $this->json([
                'status' => 'error',
                'database' => 'unhealthy',
                'sports_sync' => 'unreachable'
            ], 503);
        }

        $sportsHealth = [
            'status' => 'uninitialized',
            'last_successful_sync' => null,
            'recent_error' => null,
            'scheduler_status' => 'inactive'
        ];

        $schedulerObserved = 'inactive';
        $overallSyncStatus = 'uninitialized';
        $providersStatus = [];

        try {
            // 1. Overall sync log check
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
                    $schedulerObserved = 'observed_active';
                    $overallSyncStatus = 'healthy';
                } elseif ($secondsSince < 7200) { // Under 2 hours
                    $sportsHealth['status'] = 'stale';
                    $sportsHealth['scheduler_status'] = 'delayed';
                    $schedulerObserved = 'observed_delayed';
                    $overallSyncStatus = 'stale';
                } else {
                    $sportsHealth['status'] = 'stale';
                    $sportsHealth['scheduler_status'] = 'inactive';
                    $schedulerObserved = 'inactive';
                    $overallSyncStatus = 'stale';
                }
            } else {
                $sportsHealth['status'] = 'uninitialized';
                $sportsHealth['scheduler_status'] = 'inactive';
                $schedulerObserved = 'inactive';
                $overallSyncStatus = 'uninitialized';
            }

            // 2. Provider-specific diagnostics from sports_provider_states
            $stmtStates = $pdo->query("SELECT * FROM sports_provider_states");
            $providerStates = [];
            while ($row = $stmtStates->fetch(\PDO::FETCH_ASSOC)) {
                $providerStates[$row['provider']] = $row;
            }

            // Check API-Football
            $afConfigured = !empty(env('API_FOOTBALL_API_KEY'));
            $afState = $providerStates['api-football'] ?? [];
            $afLastLive = $afState['last_live_sync_at'] ?? null;
            $afLiveAge = $afLastLive ? max(0, time() - strtotime($afLastLive)) : null;
            $afLastSync = $afState['last_successful_sync_at'] ?? null;
            $afSyncAge = $afLastSync ? max(0, time() - strtotime($afLastSync)) : null;
            $afError = $afState['last_error'] ?? null;
            if ($afError) {
                $afError = preg_replace('/(key|token|secret|password)=([^\s&]+)/i', '$1=REDACTED', $afError);
            }
            $afBackoff = !empty($afState['backoff_until']) && (strtotime($afState['backoff_until']) > time());

            $afStatus = 'error';
            if (!$afConfigured) {
                $afStatus = 'not_configured';
            } elseif ($afBackoff) {
                $afStatus = 'degraded';
                $overallSyncStatus = 'degraded';
            } elseif ($afSyncAge !== null && $afSyncAge < 3600) {
                $afStatus = 'available';
            } elseif ($afConfigured) {
                $afStatus = 'configured';
            }

            $providersStatus['API-Football'] = [
                'configured' => $afConfigured,
                'status' => $afStatus,
                'requests_used_today' => (int)($afState['requests_today'] ?? 0),
                'requests_remaining_today' => isset($afState['quota_remaining']) ? (int)$afState['quota_remaining'] : null,
                'daily_limit' => isset($afState['quota_limit']) ? (int)$afState['quota_limit'] : null,
                'last_successful_live_sync' => $afLastLive,
                'last_live_data_age' => $afLiveAge,
                'last_error' => $afError
            ];

            // Check Football-Data
            $fdConfigured = !empty(env('FOOTBALL_DATA_API_KEY'));
            $fdState = $providerStates['football-data'] ?? [];
            $fdLastSync = $fdState['last_successful_sync_at'] ?? null;
            $fdDataAge = $fdLastSync ? max(0, time() - strtotime($fdLastSync)) : null;
            $fdError = $fdState['last_error'] ?? null;
            if ($fdError) {
                $fdError = preg_replace('/(key|token|secret|password)=([^\s&]+)/i', '$1=REDACTED', $fdError);
            }
            $fdBackoff = !empty($fdState['backoff_until']) && (strtotime($fdState['backoff_until']) > time());

            $fdStatus = 'error';
            if (!$fdConfigured) {
                $fdStatus = 'not_configured';
            } elseif ($fdBackoff) {
                $fdStatus = 'degraded';
            } elseif ($fdDataAge !== null && $fdDataAge < 3600) {
                $fdStatus = 'available';
            } elseif ($fdConfigured) {
                $fdStatus = 'configured';
            }

            $providersStatus['Football-Data'] = [
                'configured' => $fdConfigured,
                'status' => $fdStatus,
                'last_successful_sync' => $fdLastSync,
                'data_age_seconds' => $fdDataAge,
                'last_error' => $fdError
            ];
        } catch (\Throwable) {
            $overallSyncStatus = 'degraded';
            $schedulerObserved = 'unknown';
        }

        return $this->json([
            'status' => 'ok',
            'database' => 'healthy',
            'sports_sync' => $overallSyncStatus,
            'scheduler' => $schedulerObserved,
            'scheduler_note' => 'Observed activity based on recorded synchronizations; does not inspect cron daemon directly.',
            'providers' => $providersStatus,
            'sports_sync_legacy' => $sportsHealth
        ]);
    }
}
