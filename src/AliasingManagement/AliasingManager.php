<?php

namespace AdventureTech\ORM\AliasingManagement;

class AliasingManager
{
    const SEPARATOR = '/';
    const PARENT_SIGNIFIER = '..';

    private TableAliasingDTO $columnExpression;
    private int $aliasCounter = 0;

    /**
     * @param  string  $rootTableName
     * @param  array<int|string,string>  $columns
     */
    public function __construct(string $rootTableName, array $columns)
    {
        $this->columnExpression = new TableAliasingDTO($rootTableName, $columns);
    }

    /**
     * @param  string  $newRoot
     * @param  array<int|string,string>  $columns
     * @return void
     */
    public function addRelation(string $newRoot, array $columns): void
    {
        $newObject = new TableAliasingDTO('_' . $this->aliasCounter++ . '_', $columns);

        $keys = explode(self::SEPARATOR, $newRoot);
        $newKey = array_pop($keys);

        $this->resolvePath($keys)->addChild($newKey, $newObject);
    }

    /**
     * @return array<int,string>
     */
    public function getSelectColumns(): array
    {
        return $this->extractColumnNames($this->columnExpression);
    }

    /**
     * @param  TableAliasingDTO  $columnExpression
     * @return array<int,string>
     */
    private function extractColumnNames(TableAliasingDTO $columnExpression): array
    {
        $columns = [];
        foreach ($columnExpression->columns as $column) {
            $columns[] = $columnExpression->alias . '.' . $column . ' as ' . $columnExpression->alias . $column;
        }
        foreach ($columnExpression->children as $item) {
            $columns = array_merge($columns, $this->extractColumnNames($item));
        }
        return $columns;
    }

    public function getAliasedTableName(string $localRoot): string
    {
        $keys = explode(self::SEPARATOR, $localRoot);
        return $this->resolvePath($keys)->alias;
    }

    public function getSelectedColumnName(string $columnExpression, string $localRoot): string
    {
        return $this->resolveColumnExpression($localRoot, $columnExpression, '');
    }

    public function getQualifiedColumnName(string $columnExpression, string $localRoot): string
    {
        return $this->resolveColumnExpression($localRoot, $columnExpression, '.');
    }


    /**
     * @param  array<int,string>  $keys
     * @return TableAliasingDTO
     */
    private function resolvePath(array $keys): TableAliasingDTO
    {
        $columnExpression = $this->columnExpression;
        array_shift($keys);
        foreach ($keys as $key) {
            $columnExpression = $columnExpression->children[$key];
        }
        return $columnExpression;
    }

    private function resolveColumnExpression(string $localRoot, string $columnExpression, string $separator): string
    {
        $keys = explode(self::SEPARATOR, $localRoot);
        $explode = explode(self::SEPARATOR, $columnExpression);
        $column = array_pop($explode);
        foreach ($explode as $key) {
            if ($key === self::PARENT_SIGNIFIER) {
                array_pop($keys);
            } else {
                $keys[] = $key;
            }
        }
        $columnExpression = $this->resolvePath($keys);
        return $columnExpression->alias . $separator . $columnExpression->columns[$column];
    }
}
