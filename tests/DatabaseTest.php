<?php

namespace EcomDev\MySQLTestUtils;

use PHPUnit\Framework\TestCase;
use PDO;

class DatabaseTest extends TestCase
{
    /** @var DatabaseFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new DatabaseFactory();
    }

    /** @test */
    public function createsPdoConnectionFromTestEnvironment()
    {
        $connection = $this->factory->createConnection();

        $this->assertStringStartsWith(
            '5.7.',
            $connection->query('SELECT VERSION()')->fetchColumn()
        );
    }
    
    /** @test */
    public function prefixesDatabaseNameWithTestDatabasePrefixByDefault()
    {
        $config = $this->factory->createDatabase()->provideConnectionOptions();

        $this->assertStringStartsWith('test_database_', $config['database']);
    }
    
    /** @test */
    public function prefixesDatabaseNameWithCustomPrefix()
    {
        $factory = new DatabaseFactory('custom_db_prefix_');

        $config = $factory->createDatabase()->provideConnectionOptions();

        $this->assertStringStartsWith('custom_db_prefix_', $config['database']);
    }

    /** @test */
    public function eachDatabaseHasUniqueName()
    {
        $dbOne = $this->factory->createDatabase()
            ->provideConnectionOptions()['database'];
        $dbTwo = $this->factory->createDatabase()
            ->provideConnectionOptions()['database'];

        $this->assertNotEquals($dbOne, $dbTwo);
    }

    /** @test */
    public function canConnectToCreatedDatabases()
    {
        $database = $this->factory->createDatabase();
        $databaseName = $database->provideConnectionOptions()['database'];
        $this->assertContains(
            $databaseName,
            $this->fetchCurrentDatabases()
        );
    }

    /** @test */
    public function dropsDatabaseAfterObjectIsDestructed()
    {
        $database = $this->factory->createDatabase();
        $databaseName = $database->provideConnectionOptions()['database'];

        unset($database);

        $this->assertNotContains(
            $databaseName,
            $this->fetchCurrentDatabases()
        );
    }

    /** @test */
    public function containsAllConnectionSettingsRequired() 
    {
        $actualSettings = $this->factory->createDatabase()->provideConnectionOptions();

        $expectedSettings = [
            'host' => $_ENV['MYSQL_HOST'],
            'user' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD'],
            'database' => $actualSettings['database']
        ];

        $this->assertEquals($expectedSettings, $actualSettings);
    }

    /** @test */
    public function createsDatabaseSpecificPdoConnection()
    {
        $database = $this->factory->createDatabase();
        $pdo = $database->createConnection();
        $this->assertEquals(
            $database->provideConnectionOptions()['database'],
            $pdo->query('SELECT DATABASE()')->fetchColumn()
        );
    }


    /** @test */
    public function loadsFixtureFile()
    {
        $database = $this->factory->createDatabase();

        $database->loadFixture(__DIR__ . '/files/product-schema.sql');

        $this->assertEquals(
            [
                'product',
                'product_data'
            ],
            $this->fetchSingleColumn(
                'SHOW TABLES',
                $database->createConnection()
            )
        );
    }

    /** @test */
    public function loadsDataIntoTables()
    {
        $database = $this->factory->createDatabase();

        $database->loadFixture(__DIR__ . '/files/product-schema.sql');

        $database->loadArrayData([
            'product' => [
                [1, 'SKU1', 'simple'],
                [2, 'SKU2', 'simple'],
                [3, 'SKU3', 'configurable'],
            ],
            'product_data' => [
                [1, 'name', 'Product 1'],
                [2, 'name', 'Product 2'],
                [3, 'name', 'Product 3'],
            ]
        ]);

        $this->assertEquals(
            [
                [1, 'SKU1', 'simple', 'Product 1'],
                [2, 'SKU2', 'simple', 'Product 2'],
                [3, 'SKU3', 'configurable', 'Product 3'],
            ],
            $this->fetchRows(
                'SELECT p.product_id, p.sku, p.type, pd.value'
                . ' FROM product p INNER JOIN product_data pd '
                . 'ON pd.product_id = p.product_id AND pd.attribute = "name"',
                $database->createConnection()
            )
        );
    }

    /** @test */
    public function loadsCsvFileTable()
    {
        $database = $this->factory->createDatabase();

        $database->loadFixture(__DIR__ . '/files/product-schema.sql');

        $csvFile = $this->createCsvFile(
            ["sku", "type"],
            ["SKU2", "simple"],
            ["SKU3", "simple"],
            ["SKU4", "configurable"]
        );


        $database->loadCsv('product', $csvFile);

        $this->assertEquals(
            [
                [1, 'SKU2', 'simple'],
                [2, 'SKU3', 'simple'],
                [3, 'SKU4', 'configurable'],
            ],
            $this->fetchRows(
                'SELECT * FROM product',
                $database->createConnection()
            )
        );
    }

    /** @test */
    public function fetchesDataFromWholeTable()
    {
        $database = $this->factory->createDatabase();
        $database->loadFixture(__DIR__ . '/files/product-schema.sql');
        $database->loadArrayData([
            'product' => [
                [1, 'SKU1', 'type1'],
                [2, 'SKU2', 'type2'],
                [3, 'SKU3', 'type3']
            ]
        ]);

        $this->assertEquals(
            [
                [1, 'SKU1', 'type1'],
                [2, 'SKU2', 'type2'],
                [3, 'SKU3', 'type3'],
            ],
            $database->fetchTable('product')
        );
    }

    /** @test */
    public function fetchesDataFromSpecificTableColumns()
    {
        $database = $this->factory->createDatabase();
        $database->loadFixture(__DIR__ . '/files/product-schema.sql');
        $database->loadArrayData([
            'product' => [
                [1, 'SKU1', 'type1'],
                [2, 'SKU2', 'type2'],
                [3, 'SKU3', 'type3']
            ]
        ]);

        $this->assertEquals(
            [
                ['SKU1', 'type1'],
                ['SKU2', 'type2'],
                ['SKU3', 'type3'],
            ],
            $database->fetchTable('product', 'sku', 'type')
        );
    }

    /**
     * @return array
     */
    private function fetchCurrentDatabases(): array
    {
        $pdo = $this->factory->createConnection();
        $sql = 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA';
        return $this->fetchSingleColumn($sql, $pdo);
    }

    /**
     * @param string $sql
     * @param PDO $pdo
     * @return array
     */
    private function fetchSingleColumn(string $sql, PDO $pdo): array
    {
        return iterator_to_array(
            $pdo->query(
                $sql,
                PDO::FETCH_COLUMN,
                0
            )
        );
    }

    private function fetchRows(string $sql, PDO $pdo): array
    {
        return iterator_to_array(
            $pdo->query(
                $sql,
                PDO::FETCH_NUM
            )
        );
    }

    private function createCsvFile(array ...$rows): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv-file');
        $csvFile = new \SplFileObject($filePath, 'w');
        $csvFile->setCsvControl(",", '"', "\0");

        foreach ($rows as $row) {
            $csvFile->fputcsv($row);
        }

        return $filePath;
    }
}