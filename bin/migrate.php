<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;

class Migrator
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function run(): void
    {
        $migrationsDir = __DIR__ . '/../database/migrations';
        // glob() does not guarantee alphabetical order on all platforms.
        // Explicit sort ensures 001_ always runs before 002_, etc.
        $files = glob($migrationsDir . '/*.php');
        sort($files);
        
        $executed = $this->pdo->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);
        
        $batch = (int) $this->pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn() + 1;
        
        $runCount = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            if (in_array($migrationName, $executed)) {
                continue;
            }
            
            echo "Running migration: {$migrationName}...\n";
            
            try {
                $migrationClass = require $file;
                if ($migrationClass instanceof \Closure) {
                    $migrationClass($this->pdo);
                } elseif (is_object($migrationClass) && method_exists($migrationClass, 'up')) {
                    $migrationClass->up($this->pdo);
                } else {
                    throw new \Exception("Invalid migration format in {$file}");
                }

                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migrationName, $batch]);
                
                echo "Migration completed: {$migrationName}\n";
                $runCount++;
            } catch (\Exception $e) {
                echo "Migration failed: {$migrationName}\n";
                echo $e->getMessage() . "\n";
                exit(1);
            }
        }

        if ($runCount === 0) {
            echo "Nothing to migrate.\n";
        }
    }
}

$migrator = new Migrator();
$migrator->run();
