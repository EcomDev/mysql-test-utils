<?php


namespace EcomDev\MySQLTestUtils;


final class SqlGenerator
{

    public function generateColumnList(string ...$columns): string
    {
        return implode(',', array_map(
            function ($column) {
                return sprintf('`%s`', $column);
            },
            $columns
        ));
    }

    public function generatePlaceholder(int $columns, int $rows): string
    {
        $singleRow = rtrim(str_repeat('?,', $columns), ',');
        return rtrim(str_repeat(
            sprintf(
                '(%s),',
                $singleRow
            ),
            $rows
        ), ',');
    }
}