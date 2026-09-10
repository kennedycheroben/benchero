<?php
require 'app/bootstrap.php';
require 'vendor/autoload.php';
require 'app/Core/Database/Database.php';
use Benchero\Services\Auth\AuthService;
use Benchero\Services\OrganizationService;
use Benchero\Core\Database\Database;

try {
    // Override connection with UNIX socket for XAMPP
    $dsn = "mysql:unix_socket=/opt/lampp/var/mysql/mysql.sock;dbname=xqtrqexj_benchero;charset=utf8mb4";
    $db = new \PDO($dsn, 'root', '', [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    // Inject it somehow? No, Database is a singleton.
    $ref = new \ReflectionClass(Database::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, $db);
    
    $auth = new AuthService($db);
    
    $user = $auth->findUserByEmail('cherobenkennedy34@gmail.com');
    if (!$user) {
        echo "User not found\n";
        exit;
    }
    
    echo "Found user: " . $user['id'] . "\n";
    
    $orgService = new OrganizationService();
    $slug = 'test-org-' . time();
    echo "Attempting to create org with slug: " . $slug . "\n";
    
    $orgService->createOrganization('Test Org', $slug, 'KE', 'Africa/Nairobi', $user['id']);
    echo "Created org successfully!\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
