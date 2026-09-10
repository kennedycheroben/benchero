<?php

/**
 * Benchero Background Subscription Synchronization CLI Command
 *
 * Usage: php bin/sync_subscriptions.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\SubscriptionService;

$service = new SubscriptionService();
$service->syncSubscriptionStatus();

echo "Subscription status synchronization completed successfully.\n";
