<?php


namespace EcomDev\MySQLTestUtils;

use PDO;

final class Database
{
    private $databaseName;
    /**
     * @var callable
     */
    private $cleanUpResources;
    /**
     * @var array
     */
    private $connectionOptions;

    public function __construct(string $databaseName, array $connectionOptions, callable $cleanUpResources)
    {
        $this->databaseName = $databaseName;
        $this->cleanUpResources = $cleanUpResources;
        $this->connectionOptions = $connectionOptions;
    }

    public function provideConnectionOptions(): array
    {
        return $this->connectionOptions + ['database' => $this->databaseName];
    }

    public function __destruct()
    {
        ($this->cleanUpResources)();
    }

    public function createConnection(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8;',
                $this->connectionOptions['host'],

                $this->databaseName
            ),
            $this->connectionOptions['user'],
            $this->connectionOptions['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    public function loadFixture(string $fileName): void
    {
        shell_exec(
            sprintf(
                '%s -h%s -u%s -p%s %s < %s 2>/dev/null',
                $_ENV['MYSQL_CLIENT'] ?? 'mysql',
                $this->connectionOptions['host'],
                $this->connectionOptions['user'],
                $this->connectionOptions['password'],
                $this->databaseName,
                $fileName
            )
        );
    }

    public function loadData(array $tableData): void
    {
        $connection = $this->createConnection();

        foreach ($tableData as $tableName => $rows) {
            $singleRow = rtrim(str_repeat('?,', count($rows[0])), ',');
            $sql = sprintf(
                'INSERT INTO `%s` VALUES %s',
                $tableName,
                rtrim(str_repeat(sprintf('(%s),', $singleRow), count($rows)), ',')
            );

            $stmt = $connection->prepare($sql);
            $stmt->execute(
                array_reduce(
                    $rows, function ($initial, $item) {
                    return array_merge($initial, $item);
                }, [])
            );
        }
    }
}