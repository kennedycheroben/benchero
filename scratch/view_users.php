<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

$pdo = Database::getConnection();
$users = Database::query("SELECT id, name, email, role, is_platform_admin, created_at FROM users")->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']} | Role: {$u['role']} | Admin: {$u['is_platform_admin']} | Created: {$u['created_at']}\n";
}
