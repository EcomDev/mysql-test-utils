<?php


namespace EcomDev\MySQLTestUtils;

use PHPUnit\Framework\TestCase;

class SqlGeneratorTest extends TestCase
{
    /** @var SqlGenerator */
    private $sqlGenerator;

    protected function setUp(): void
    {
        $this->sqlGenerator = new SqlGenerator();
    }

    /** @test */
    public function generatesColumnList()
    {
        $this->assertEquals(
            '`column1`,`column2`,`column3`',
            $this->sqlGenerator->generateColumnList('column1', 'column2', 'column3')
        );
    }

    /** @test */
    public function generatesSingleValuePlaceholder()
    {
        $this->assertEquals(
            '(?)',
            $this->sqlGenerator->generatePlaceholder(1, 1)
        );
    }

    /** @test */
    public function generatesMultipleValuesInOneRowPlaceholder()
    {
        $this->assertEquals(
            '(?,?,?)',
            $this->sqlGenerator->generatePlaceholder(3, 1)
        );
    }

    /** @test */
    public function generatesMultipleValuesOverMultipleRowsInPlaceholders()
    {
        $this->assertEquals(
            '(?,?,?,?),(?,?,?,?),(?,?,?,?)',
            $this->sqlGenerator->generatePlaceholder(4, 3)
        );
    }



}