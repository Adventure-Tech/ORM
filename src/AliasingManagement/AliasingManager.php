<?php

namespace AdventureTech\ORM\AliasingManagement;

use AdventureTech\ORM\Exceptions\InvalidColumnExpressionException;

class AliasingManager
{
    public const SEPARATOR = '/';
    public const PARENT_SIGNIFIER = '..';

    private TableAliasingDTO $dto;
    private int $aliasCounter = 0;

    /**
     * @param  string  $rootTableName
     * @param  array<int|string,string>  $columns
     */
    public function __construct(string $rootTableName, array $columns)
    {
        $this->dto = new TableAliasingDTO($rootTableName, $columns);
    }

    /**
     * @param  string  $newRoot
     * @param  array<int|string,string>  $columns
     * @return void
     */
    public function addRelation(string $newRoot, array $columns): void
    {
        $newDto = new TableAliasingDTO('_' . $this->aliasCounter++ . '_', $columns);

        $path = explode(self::SEPARATOR, $newRoot);
        $newRelation = array_pop($path);

        $this->resolvePath($path)->addChild($newRelation, $newDto);
    }

    /**
     * @return array<int,string>
     */
    public function getSelectColumns(): array
    {
        return $this->extractColumnNames($this->dto);
    }

    public function getAliasedTableName(string $localRoot): string
    {
        $path = explode(self::SEPARATOR, $localRoot);
        return $this->resolvePath($path)->alias;
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
     * @param  TableAliasingDTO  $dto
     * @return array<int,string>
     */
    private function extractColumnNames(TableAliasingDTO $dto): array
    {
        $columns = [];
        foreach ($dto->columns as $column) {
            $columns[] = $dto->alias . '.' . $column . ' as ' . $dto->alias . $column;
        }
        foreach ($dto->children as $item) {
            $columns = array_merge($columns, $this->extractColumnNames($item));
        }
        return $columns;
    }

    /**
     * @param  array<int,string>  $path
     * @return TableAliasingDTO
     */
    private function resolvePath(array $path): TableAliasingDTO
    {
        $dto = $this->dto;
        array_shift($path);
        foreach ($path as $key) {
            if (!isset($dto->children[$key])) {
                throw new  InvalidColumnExpressionException('Tried to access relation which was not added');
            }
            $dto = $dto->children[$key];
        }
        return $dto;
    }

    private function resolveColumnExpression(string $localRoot, string $columnExpression, string $separator): string
    {
        $localRootPath = explode(self::SEPARATOR, $localRoot);
        $relativePath = explode(self::SEPARATOR, $columnExpression);
        $column = array_pop($relativePath);
        foreach ($relativePath as $key) {
            if ($key === self::PARENT_SIGNIFIER) {
                array_pop($localRootPath);
            } else {
                $localRootPath[] = $key;
            }
        }
        $dto = $this->resolvePath($localRootPath);
        return $dto->alias . $separator . $dto->columns[$column];
    }
}
