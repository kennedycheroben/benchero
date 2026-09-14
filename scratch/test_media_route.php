<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Http\Request;
use Benchero\Controllers\Tenant\MediaController;

$request = new Request([], [], ['REQUEST_METHOD' => 'GET'], [], []);
echo "getFlash test: " . var_export($request->getFlash('test'), true) . "\n";
$request->setFlash('test', 'hello');
echo "getFlash test after set: " . var_export($request->getFlash('test'), true) . "\n";

echo "Testing MediaController instantiation...\n";
$controller = new MediaController();
echo "Success!\n";
