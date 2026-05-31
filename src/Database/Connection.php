<?php

declare(strict_types=1);

namespace MrThito\MicroService\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private ?PDO $pdo = null;

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: string}  $database
     */
    public function __construct(private readonly array $database) {}

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        if ($this->database['database'] === '') {
            throw new RuntimeException('DB_DATABASE is not configured.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->database['host'],
            $this->database['port'],
            $this->database['database'],
        );

        try {
            $this->pdo = new PDO($dsn, $this->database['username'], $this->database['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: '.$exception->getMessage(), 0, $exception);
        }

        return $this->pdo;
    }
}
