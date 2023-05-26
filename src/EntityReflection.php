<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Exceptions\EntityInstantiationException;
use AdventureTech\ORM\Exceptions\EntityReflectionInstantiationException;
use AdventureTech\ORM\Exceptions\MultipleIdColumnsException;
use AdventureTech\ORM\Exceptions\NullReflectionTypeException;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\ManagedDatetimes\ManagedDatetime;
use AdventureTech\ORM\Mapping\ManagedDatetimes\ManagedDatetimeAnnotation;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Relations\Relation;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
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
     * @var Collection<string, Mapper<mixed>>
     */
    private Collection $mappers;

    /**
     * @var Collection<string, Linker<T,object>>
     */
    private Collection $linkers;
    /**
     * @var Entity<T>
     */
    private Entity $entityAttribute;
    private string $id;
    /**
     * @var Collection<string,ManagedDatetime>
     */
    private Collection $managedDatetimes;

    /**
     * @template A
     * @param  class-string<A>  $class
     * @return EntityReflection<A>
     */
    public static function new(string $class): EntityReflection
    {
        // TODO: cache
        return new self($class);
    }

    /**
     * @param  class-string<T>  $class
     */
    private function __construct(private readonly string $class)
    {
        try {
            $this->reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new EntityReflectionInstantiationException($class);
        }
        $entityAttributes = $this->reflectionClass->getAttributes(Entity::class);
        if (count($entityAttributes) !== 1) {
            throw new EntityReflectionInstantiationException($class);
        }

        $this->entityAttribute = $entityAttributes[0]->newInstance();
        $this->mappers = Collection::empty();
        $this->linkers = Collection::empty();
        $this->managedDatetimes = Collection::empty();

        foreach ($this->reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Id) {
                    $this->setId($property->getName());
                } elseif ($attributeInstance instanceof Column) {
                    $this->registerMapper($attributeInstance, $property);
                } elseif ($attributeInstance instanceof ManagedDatetimeAnnotation) {
                    $this->managedDatetimes->put($property->getName(), $attributeInstance->getManagedDatetime());
                } elseif ($attributeInstance instanceof Relation) {
                    $this->registerLinker($attributeInstance, $property);
                }
            }
        }

        foreach ($this->managedDatetimes as $property => $managedDatetime) {
            $managedDatetime->setMapper($this->mappers->get($property));
            $this->mappers->forget($property);
        }
    }

    /**
     * @return T
     */
    public function newInstance()
    {
        try {
            return $this->reflectionClass->newInstanceWithoutConstructor();
        } catch (ReflectionException $e) {
            throw new EntityInstantiationException($this->class, $e);
        }
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
     * @return Collection<string, Mapper<mixed>>
     */
    public function getMappers(): Collection
    {
        return $this->mappers;
    }

    /**
     * @return Collection<string,Linker<T,object>>
     */
    public function getLinkers(): Collection
    {
        return $this->linkers;
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
        foreach ($this->mappers as $mapper) {
            foreach ($mapper->getColumnNames() as $columnName) {
                $columnNames[$columnName] = $columnName;
            }
        }
        foreach ($this->managedDatetimes as $managedDatetime) {
            $columnName = $managedDatetime->getColumnName();
            $columnNames[$columnName] = $columnName;
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
     * @return Collection<string,ManagedDatetime>
     */
    public function getManagedDatetimes(): Collection
    {
        return $this->managedDatetimes;
    }

    private function setId(string $propertyName): void
    {
        if (isset($this->id)) {
            throw new MultipleIdColumnsException();
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
        $this->mappers->put(
            $property->getName(),
            $column->getMapper($property)
        );
    }

    /**
     * @param  Relation<T,object>  $relation
     * @param  ReflectionProperty  $property
     * @return void
     */
    private function registerLinker(Relation $relation, ReflectionProperty $property): void
    {
        /** @var ReflectionNamedType|null $type */
        $type = $property->getType();
        if (is_null($type)) {
            throw new NullReflectionTypeException();
        }
        /** @var class-string<object> $propertyType */
        $propertyType = $type->getName();
        $this->linkers->put(
            $property->getName(),
            $relation->getLinker($property->getName(), $propertyType, $this->class)
        );
    }
}
