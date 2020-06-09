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
    /**
     * @var SqlGenerator
     */
    private $sqlGenerator;

    public function __construct(
        string $databaseName,
        array $connectionOptions,
        callable $cleanUpResources,
        SqlGenerator $sqlGenerator
    ) {
        $this->databaseName = $databaseName;
        $this->cleanUpResources = $cleanUpResources;
        $this->connectionOptions = $connectionOptions;
        $this->sqlGenerator = $sqlGenerator;
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

    public function loadArrayData(array $tableData): void
    {
        $connection = $this->createConnection();

        foreach ($tableData as $tableName => $rows) {
            $sql = sprintf(
                'INSERT INTO `%s` VALUES %s',
                $tableName,
                $this->sqlGenerator->generatePlaceholder(count($rows[0]), count($rows))
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

    public function loadCsv(string $tableName, string $fileName): void
    {
        $csvObject = new \SplFileObject($fileName);
        $csvObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $csvObject->setCsvControl(',', '"', "\0");

        $columns = [];
        $parameters = [];
        $rowCount = 0;

        foreach ($csvObject as $row) {
            if (!$row) {
                continue;
            }

            if (!$columns) {
                $columns = $row;
                continue;
            }

            $parameters = array_merge($parameters, $row);
            $rowCount ++;
        }

        $connection = $this->createConnection();
        $stmt = $connection->prepare(
            sprintf(
                'INSERT INTO `%s` (%s) VALUES %s',
                $tableName,
                $this->sqlGenerator->generateColumnList(...$columns),
                $this->sqlGenerator->generatePlaceholder(count($columns), $rowCount)
            )
        );

        $stmt->execute($parameters);
    }

    public function fetchTable(string $tableName, string ...$columns): array
    {
        $connection = $this->createConnection();
        return iterator_to_array($connection->query(
            sprintf(
                'SELECT %s FROM %s',
                $columns ? $this->sqlGenerator->generateColumnList(...$columns) : '*',
                $tableName
            ),
            PDO::FETCH_NUM
        ));
    }
}