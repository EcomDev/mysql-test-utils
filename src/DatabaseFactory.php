<?php

namespace EcomDev\MySQLTestUtils;

use PDO;

final class DatabaseFactory
{
    /**
     * @var string
     */
    private $databaseNamePrefix;

    public function __construct(string $databaseNamePrefix = 'test_database_')
    {
        $this->databaseNamePrefix = $databaseNamePrefix;
    }

    public function createConnection(): PDO
    {
        $connection = $this->connectionOptions();
        return new PDO(
            sprintf(
                'mysql:host=%s;charset=utf8;',
                $connection['host']
            ),
            $connection['user'],
            $connection['password'],
            [
                PDO::ERRMODE_EXCEPTION => true
            ]
        );
    }

    private function connectionOptions(): array
    {
        return [
            'host' => $_ENV['MYSQL_HOST'],
            'user' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD']
        ];
    }

    public function createDatabase(): Database
    {
        $connection = $this->createConnection();
        $databaseName = uniqid($this->databaseNamePrefix);

        $connection->exec(sprintf('CREATE DATABASE `%s`', $databaseName));

        return new Database(
            $databaseName,
            $this->connectionOptions(),
            function () use ($connection, $databaseName) {
                $connection->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName));
            }
        );
    }
}