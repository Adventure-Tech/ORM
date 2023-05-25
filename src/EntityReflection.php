<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Relations\Relation;
use ErrorException;
use Illuminate\Support\Collection;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

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
     * @var Collection<string, Mapper<mixed>>
     */
    private Collection $mappers;

    /**
     * @var Collection<string, Relation<T,object>>
     */
    private Collection $relations;
    /**
     * @var string
     */
    private string $id;
    /**
     * @var Entity<T>
     */
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

        $this->entityAttribute = $entityAttribute->newInstance();

        $this->mappers = Collection::empty();
        $this->relations = Collection::empty();

        foreach ($this->reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Id) {
                    $this->setId($property->getName());
                } elseif ($attributeInstance instanceof Column) {
                    $this->registerMapper($attributeInstance, $property);
                } elseif ($attributeInstance instanceof Relation) {
                    $this->registerRelation($attributeInstance, $property);
                }
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
        return $this->entityAttribute->getTable($this->class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Collection<string, Column<mixed>>
     */
    public function getMappers(): Collection
    {
        return $this->mappers;
    }

    /**
     * @return Collection<string,Relation<T,object>>
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
        foreach ($this->mappers as $column) {
            foreach ($column->getColumnNames() as $columnName) {
                $columnNames[$columnName] = $columnName;
            }
        }
        if ($alias === '') {
            return array_map(
                fn (string $column): string => $this->getTableName() . '.' . $column,
                $columnNames
            );
        } else {
            return array_map(
                fn (string $column): string => $alias . '.' . $column . ' as ' . $alias . $column,
                $columnNames
            );
        }
    }

    /**
     * @return class-string|null
     */
    public function getRepository(): ?string
    {
        return $this->entityAttribute->getRepository();
    }


    /**
     * @return string|null
     */
    public function getSoftDeleteColumn(): ?string
    {
        return !is_null($this->softDeleteColumn) ?
            $this->getTableName() . '.' . $this->softDeleteColumn
            : null;
    }

    private function setId(string $propertyName): void
    {
        if (isset($this->id)) {
            throw new LogicException('Cannot have multiple ID columns');
        }
        $this->id = $propertyName;
    }

    /**
     * @param  Column<mixed>  $column
     * @param  ReflectionProperty  $property
     * @return void
     */
    private function registerMapper(Column $column, ReflectionProperty $property): void
    {
        $this->mappers->put($property->getName(), $column->getMapper($property));
        if ($column instanceof DeletedAtColumn) {
            $columnNames = $column->getColumnNames();
            if (count($columnNames) !== 1) {
                // TODO: custom exception
                throw new LogicException('Invalid DeletedAtColumn');
            }
            $this->softDeleteColumn = $columnNames[0];
        }
    }

    /**
     * @param  Relation<T,object>  $relation
     * @param  ReflectionProperty  $property
     * @return void
     */
    private function registerRelation(Relation $relation, ReflectionProperty $property): void
    {
        // TODO: check if `$property->getType()?->getName()` is correc
        $type = $property->getType();
        if (is_null($type)) {
            // TODO: custom exception
            throw new RuntimeException('Encountered null in reflection type');
        }
        $relation->initialize($property->getName(), $type->getName(), $this->class);
        $this->relations->put($property->getName(), $relation);
    }
}
