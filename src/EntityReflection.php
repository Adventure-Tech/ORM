<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Columns\DeletedAtColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Relations\Relation;
use ErrorException;
use Illuminate\Support\Collection;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * @template T of object
 */
class EntityReflection
{
    /**
     * @var ReflectionClass<T>
     */
    private ReflectionClass $reflectionClass;

    /**
     * @var Collection<string, Column>
     */
    private Collection $columns;

    /**
     * @var Collection<string, Relation>
     */
    private Collection $relations;
    /**
     * @var string
     */
    private string $id;
    private Entity $entityAttribute;

    private ?string $softDeleteColumn = null;

    /**
     * @param  class-string<T>  $class
     */
    public function __construct(private readonly string $class)
    {
        try {
            $this->reflectionClass = new ReflectionClass($class);
            $entityAttribute = $this->reflectionClass->getAttributes(Entity::class)[0];
        } catch (ReflectionException | ErrorException) {
            // TODO: better checks
            throw new LogicException('Repository only works with entities [' . $this->class . ']');
        }

        $this->entityAttribute = $entityAttribute->newInstance()->resolveDefaults($class);

        $this->columns = Collection::empty();
        $this->relations = Collection::empty();

        foreach ($this->reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Id) {
                    $this->setId($property->getName());
                } elseif ($attributeInstance instanceof Column) {
                    $this->registerColumn($attributeInstance, $property);
                } elseif ($attributeInstance instanceof Relation) {
                    $this->registerRelation($attributeInstance, $property);
                }
                //                match($attribute->getName()) {
                //                    Id::class => $this->setId($property->getName()),
                //                    Column::class => $this->registerColumn($attribute, $property),
                //                    Relation::class => $this->registerRelation($attribute, $property),
                //                    default => null
                //                };
            }
        }
    }

    /**
     * @return T
     * @throws ReflectionException
     */
    public function newInstance()
    {
        // TODO: handle ReflectionException
        return $this->reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->entityAttribute->getTable();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Collection<string, Column>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    /**
     * @return Collection<string,Relation>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param  string  $alias
     *
     * @return array<string, string>
     */
    public function getSelectColumns(string $alias = ''): array
    {
        $columnNames = [];
        foreach ($this->columns as $column) {
            foreach ($column->getColumnNames() as $columnName) {
                $columnNames[$columnName] = $columnName;
            }
        }
        if ($alias === '') {
            return array_map(
                fn (string $column) => $this->getTableName() . 'Repository' . $column,
                $columnNames
            );
        } else {
            return array_map(
                fn (string $column) => $alias . '.' . $column . ' as ' . $alias . $column,
                $columnNames
            );
        }
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->entityAttribute->getRepository();
    }


    /**
     * @return string|null
     */
    public function getSoftDeleteColumn(): ?string
    {
        return !is_null($this->softDeleteColumn) ?
            $this->getTableName() . 'Repository' . $this->softDeleteColumn
            : null;
    }

    private function setId(string $propertyName): void
    {
        if (isset($this->id)) {
            throw new LogicException('Cannot have multiple ID columns');
        }
        $this->id = $propertyName;
    }

    private function registerColumn(Column $column, ReflectionProperty $property): void
    {
        $column->resolveDefault($property);
        $this->columns->put($property->getName(), $column);
        if ($column instanceof DeletedAtColumn) {
            $columnNames = $column->getColumnNames();
            if (count($columnNames) !== 1) {
                // TODO: custom exception
                throw new LogicException('Invalid DeletedAtColumn');
            }
            $this->softDeleteColumn = $columnNames[0];
        }
    }

    private function registerRelation(Relation $relation, ReflectionProperty $property): void
    {
        $relation->resolveDefault($property->getName(), $property->getType(), $this->class);
        $this->relations->put($property->getName(), $relation);
    }
}
