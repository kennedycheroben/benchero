<?php
require 'vendor/autoload.php';
require 'app/Core/Database/Database.php';

// Mock session
$_SESSION = ['_user_id' => '01HXXXXXXX'];

$service = new \Benchero\Services\OrganizationService();
try {
    $service->createOrganization('Test Club', 'test-club', 'KE', 'Africa/Nairobi', '01HXXXXXXX');
    echo "Success\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
