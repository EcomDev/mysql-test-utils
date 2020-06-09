# MySQL Test Utilities 
Bare minimum MySQL Test utilities for writing automated tests of software that relies on MySQL as data storage.

This package works great with any type of MySQL setup as soon as user for testing can create own databases.
Each instance of the database class creates a new unique database on the server that gets dropped upon test case completion.

## Configuration
In your PHPUnit xml you need to specify the connection settings for mysql connection and executable for mysql client. 

Down below is an example of phpunit xml configuration with docker-compose database setup. 
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.2/phpunit.xsd"
   bootstrap="vendor/autoload.php">
    <testsuite name="MySQL Test Utils Suite">
        <directory suffix="Test.php">tests/</directory>
    </testsuite>
    <php>
        <env name="MYSQL_PASSWORD" value="tests" />
        <env name="MYSQL_USER" value="root" />
        <env name="MYSQL_HOST" value="127.0.0.1" />
        <env name="MYSQL_CLIENT" value="docker-compose exec -T mysql mysql" />
    </php>
</phpunit>
``` 

```yaml
version: "3"
services:
  mysql:
    image: percona/percona-server:5.7
    ports:
      - 3306:3306
    environment:
      MYSQL_ROOT_PASSWORD: tests
```

## Examples

#### Database gets created on each test
```php
use PHPUnit\Framework\TestCase;
use EcomDev\MySQLTestUtils\DatabaseFactory;

class SomeDatabaseRelatedTest extends TestCase
{
    private $database;
    
    protected function setUp() : void
    {
         $this->database = (new DatabaseFactory())->createDatabase();
         $this->database->loadFixture('some/path/to/schema.sql');
    }
    
    public function testSomethingIsWritten() 
    {
        $this->assertEquals(
            [
                ['value1', 'value2']
            ],
            $this->database->fetchTable('some_table_name', 'column1', 'column2')
        );
    }
}
```

#### Database gets created on each test class

```php
use PHPUnit\Framework\TestCase;
use EcomDev\MySQLTestUtils\DatabaseFactory;

class SomeDatabaseRelatedTest extends TestCase
{
    private static $database;
    
    public static function setUpBeforeClass() : void
    {
         self::$database = (new DatabaseFactory())->createDatabase();
         self::$database->loadFixture('some/path/to/schema.sql');
    }
    
    public static function tearDownAfterClass() : void
    {
         self::$database = null;
    }

    public function testSomethingIsWritten() 
    {
        $this->assertEquals(
            [
                ['value1', 'value2']
            ],
            self::$database->fetchTable('some_table_name', 'column1', 'column2')
        );
    }
}
```