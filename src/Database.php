<?php
declare(strict_types=1);

namespace Src;
use PDO;
class Database {
    private static $pdo = null;

    private static function getConnection() {
        $config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "pgsql:host=".$config['database']['host'].";dbname=".$config['database']['dbname'],
                $config['database']['username'],
                $config['database']['password']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
    public static function prepare($sql) {
        return self::getConnection()->prepare($sql);
    }
}