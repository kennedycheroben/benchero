<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Controllers\Public\PublicClubController;

try {
    $db = Database::getConnection();
    
    // Test 'test-club-alpha'
    $request = new Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    $controller = new PublicClubController();
    $response = $controller->show($request, ['slug' => 'test-club-alpha']);
    echo "SUCCESS for test-club-alpha\n";
    echo substr($response->getContent(), 0, 500);

} catch (\Throwable $e) {
    echo "EXCEPTION CAUGHT:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
