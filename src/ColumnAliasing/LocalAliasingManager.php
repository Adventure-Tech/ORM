<?php

namespace AdventureTech\ORM\ColumnAliasing;

readonly class LocalAliasingManager
{
    public function __construct(private AliasingManager $manager, public string $localRoot)
    {
    }

    public function getQualifiedColumnName(string $column): string
    {
        return $this->manager->getQualifiedColumnName($column, $this->localRoot);
    }

    public function getSelectedColumnName(string $column): string
    {
        return $this->manager->getSelectedColumnName($column, $this->localRoot);
    }

    public function getAliasedTableName(): string
    {
        return $this->manager->getAliasedTableName($this->localRoot);
    }
}
