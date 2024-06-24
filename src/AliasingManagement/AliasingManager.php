<?php

namespace AdventureTech\ORM\AliasingManagement;

use AdventureTech\ORM\Exceptions\AliasingException;

class AliasingManager
{
    public const SEPARATOR = '/';
    public const PARENT_SIGNIFIER = '..';

    private TableAliasingDto $dto;
    private int $aliasCounter = 0;

    /**
     * @param  string  $rootTableName
     * @param  array<int|string,string>  $columns
     */
    public function __construct(string $rootTableName, array $columns)
    {
        $this->dto = new TableAliasingDto($rootTableName, $columns);
    }

    /**
     * @param  string  $newRoot
     * @param  array<int|string,string>  $columns
     * @return void
     */
    public function addRelation(string $newRoot, array $columns): void
    {
        $newDto = new TableAliasingDto('_' . $this->aliasCounter++ . '_', $columns);

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
     * @param  TableAliasingDto  $dto
     * @return array<int,string>
     */
    private function extractColumnNames(TableAliasingDto $dto): array
    {
        $columns = [];
        foreach ($dto->columns as $column) {
            $columns[] = $dto->alias . '.' . $column . ' as ' . $dto->alias . $column;
        }
        $childrenColumns = [];
        foreach ($dto->children as $item) {
            $childrenColumns[] = $this->extractColumnNames($item);
        }
        return array_merge($columns, ...$childrenColumns);
    }

    /**
     * @param  array<int,string>  $path
     * @return TableAliasingDto
     */
    private function resolvePath(array $path): TableAliasingDto
    {
        $dto = $this->dto;
        array_shift($path);
        foreach ($path as $key) {
            if (!isset($dto->children[$key])) {
                throw new  AliasingException(
                    'Failed to resolve key "' . $key . '". ' . (
                        count($dto->children) > 0
                            ? 'Available keys are : "' . implode('", "', array_keys($dto->children)) . '".'
                            : 'No keys available.')
                );
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
