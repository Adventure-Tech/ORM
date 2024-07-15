<?php

namespace AdventureTech\ORM\Persistence\Persistors;

use AdventureTech\ORM\Caching\ColumnTypeCache;
use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Persistence\Persistors\Traits\ChecksEntityType;
use AdventureTech\ORM\Persistence\Persistors\Traits\SerializesEntities;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_keys;
use function collect;

/**
 * @template T of object
 * @implements Persistor<T>
 */
class UpdatePersistor implements Persistor
{
    /**
     * @use SerializesEntities<T>
     */
    use SerializesEntities;
    /**
     * @use ChecksEntityType<T>
     */
    use ChecksEntityType;

    /**
     * @var array<string,string>
     */
    protected array $entityCheckMessages = [
        'checkType' => 'Cannot update entity of type "%s" with persistence manager configured for entities of type "%s".',
        'checkCount' => 'Could not update all entities. Updated %d out of %d.',
        'checkIdSet' => 'Must set ID column when updating entities.',
    ];
    /**
     * @var array<int,T>
     */
    protected array $entities = [];
    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $values = [];

    /**
     * @param  T  $entity
     * @param  array<int,mixed>  $args
     * @return $this
     */
    public function add(object $entity, array $args = null): self
    {
        $this->checkType($entity);
        $this->checkIdSet($entity);

        // TODO: actually remove soft deletes from serialization & add WHERE clause to only update non-soft-deleted records
        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            EntityAccessorService::set($entity, $property, null);
        }
        foreach ($this->entityReflection->getManagedColumns() as $property => $managedColumn) {
            $updateValue = $managedColumn->getUpdateValue();
            if (!is_null($updateValue)) {
                EntityAccessorService::set($entity, $property, $updateValue);
            }
        }
        $this->entities[] = $entity;
        $this->values[] = $this->serializeEntity($entity, true);
        return $this;
    }

    public function persist(): void
    {
        if (count($this->entities) !== 0) {
            $updatedRowsCount =  DB::update($this->getUpdateSql(), Arr::flatten($this->values, 1));
            $this->checkCount(count($this->values), $updatedRowsCount);
        }
    }


    private function getUpdateSql(): string
    {
        $tmpTableName = '_tmp_';
        $columns = collect(array_keys($this->values[0]));
        $columnTypes = ColumnTypeCache::get($this->entityReflection->getTableName());
        $placeholders = [];
        $placeholders[] = $columns->implode(static fn($column) => '?::' . $columnTypes[$column], ', ');
        $placeholderRow = $columns->implode(static fn($column) => '?', ', ');
        array_push($placeholders, ...Collection::times(count($this->values) - 1, static fn() => $placeholderRow));

        // get DB column names for managed columns
        $managedColumns = [];
        $mappers = $this->entityReflection->getMappers();
        foreach ($this->entityReflection->getManagedColumns() as $property => $_) {
            foreach ($mappers[$property]->getColumnNames() as $columnNames) {
                $managedColumns[$columnNames] = $columnNames;
            }
        }

        $tableName = $this->entityReflection->getTableName();
        $idColumn = $this->entityReflection->getIdColumn();
        $sql = sprintf(
            "UPDATE \"%s\" SET %s FROM (VALUES (%s)) AS %s (\"%s\") WHERE \"%s\".\"%s\" = \"%s\".\"%s\"",
            $tableName,
            $columns
                ->filter(fn($column) => $column !== $this->entityReflection->getIdColumn())
                ->implode(fn ($column) => array_key_exists($column, $managedColumns)
                    ? "\"$column\" = COALESCE(\"$tmpTableName\".\"$column\", \"$tableName\".\"$column\")"
                    : "\"$column\" = \"$tmpTableName\".\"$column\"", ', '),
            implode('), (', $placeholders),
            $tmpTableName,
            $columns->implode('", "'),
            $tableName,
            $idColumn,
            $tmpTableName,
            $idColumn
        );
        foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
            foreach ($this->entityReflection->getMappers()[$property]->getColumnNames() as $columnName) {
                $sql .= " AND \"$tableName\".\"$columnName\" IS NULL";
            }
        }
        return $sql;
    }
}
