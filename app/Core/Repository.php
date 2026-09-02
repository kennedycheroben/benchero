<?php

namespace Teamora\Core;

use Teamora\Core\Database\Database;
use PDO;

abstract class Repository
{
    protected function query(string $sql, array $params = []): false|\PDOStatement
    {
        return Database::query($sql, $params);
    }
}
