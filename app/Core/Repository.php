<?php

namespace Benchero\Core;

use Benchero\Core\Database\Database;
use PDO;

abstract class Repository
{
    protected function query(string $sql, array $params = []): false|\PDOStatement
    {
        return Database::query($sql, $params);
    }
}
