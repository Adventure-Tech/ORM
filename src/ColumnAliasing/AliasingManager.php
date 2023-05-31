<?php

namespace AdventureTech\ORM\ColumnAliasing;

class AliasingManager
{
    const SEPARATOR = '/';
    const PARENT_SIGNIFIER = '..';

    private ColumnExpression $columnExpression;
    private int $aliasCounter = 0;

    public function __construct(string $rootTableName, array $columns)
    {
        $this->columnExpression = new ColumnExpression($rootTableName, $columns);
    }

    public function addRelation(string $newRoot, array $columns): void
    {
        $newObject = new ColumnExpression('_' . $this->aliasCounter++ . '_', $columns);

        $keys = explode(self::SEPARATOR, $newRoot);
        $newKey = array_pop($keys);

        $this->resolvePath($keys)->addChild($newKey, $newObject);
    }

    public function getSelectColumns(): array
    {
        return $this->extractColumnNames($this->columnExpression);
    }
    private function extractColumnNames(ColumnExpression $columnExpression): array
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


    private function resolvePath(array $keys): ColumnExpression
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
