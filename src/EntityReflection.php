<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Exceptions\EntityInstantiationException;
use AdventureTech\ORM\Exceptions\EntityReflectionInstantiationException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Exceptions\MultipleIdColumnsException;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Mapping\Columns\ColumnAnnotation;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\ManagedColumns\ManagedColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Relations\RelationAnnotation;
use AdventureTech\ORM\Mapping\SoftDeletes\SoftDeleteAnnotation;
use AdventureTech\ORM\Repository\Repository;
use ArgumentCountError;
use Illuminate\Support\Collection;
use Mockery\Mock;
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
    private bool $hasAutogeneratedId;
    /**
     * @var Collection<string,ManagedColumnAnnotation<mixed>>
     */
    private Collection $managedColumns;
    /**
     * @var Collection<string,SoftDeleteAnnotation>
     */
    private Collection $softDeletes;

    /**
     * @template A of object
     * @param  class-string<A>  $class
     * @return EntityReflection<A>
     */
    public static function new(string $class): EntityReflection
    {
        // TODO: cache
        if (isset(self::$fake)) {
            /** @var EntityReflection<A> $mock */
            $mock = self::$fake;
            return $mock;
        }
        return new self($class);
    }

    /**
     * @var Mock|EntityReflection<object>|null
     */
    private static Mock|EntityReflection|null $fake;

    /**
     * @return Mock|EntityReflection<object>
     */
    public static function fake(): Mock|EntityReflection
    {
        self::$fake = mock(self::class)->makePartial();
        return self::$fake;
    }

    public static function resetFake(): void
    {
        self::$fake = null;
    }

    /**
     * @param  class-string<T>  $class
     */
    private function __construct(private readonly string $class)
    {
        try {
            /** @throws ReflectionException */
            $this->reflectionClass = new RefLectionClass($class);
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
        $this->managedColumns = Collection::empty();
        $this->softDeletes = Collection::empty();

        foreach ($this->reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Id) {
                    $this->setId($property->getName(), $attributeInstance);
                } elseif ($attributeInstance instanceof ColumnAnnotation) {
                    $this->registerMapper($attributeInstance, $property);
                } elseif ($attributeInstance instanceof RelationAnnotation) {
                    $this->registerLinker($attributeInstance, $property);
                } elseif ($attributeInstance instanceof ManagedColumnAnnotation) {
                    $this->managedColumns->put($property->getName(), $attributeInstance);
                } elseif ($attributeInstance instanceof SoftDeleteAnnotation) {
                    $this->softDeletes->put($property->getName(), $attributeInstance);
                }
            }
        }
        if (!isset($this->id)) {
            throw new MissingIdException('Entity must have an ID column');
        }
        if (!$this->mappers->has($this->id)) {
            throw new MissingIdException('ID column of an entity must be mapper via a column mapper annotation');
        }
    }

    /**
     * @return T
     */
    public function newInstance()
    {
        try {
            return $this->reflectionClass->newInstance();
        } catch (ReflectionException | ArgumentCountError $e) {
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
    public function getIdColumn(): string
    {
        /** @var Mapper<mixed> $mapper */
        $mapper = $this->getMappers()->get($this->getIdProperty());
        return $mapper->getColumnNames()[0];
    }
    /**
     * @return string
     */
    public function getIdProperty(): string
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
     * @return class-string<T>
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array<string, string>
     */
    public function getSelectColumns(): array
    {
        $columnNames = [];
        foreach ($this->mappers as $mapper) {
            foreach ($mapper->getColumnNames() as $columnName) {
                $columnNames[$columnName] = $columnName;
            }
        }
        foreach ($this->linkers as $linker) {
            if ($linker instanceof OwningLinker) {
                $columnNames[$linker->getForeignKey()] = $linker->getForeignKey();
            }
        }
        return $columnNames;
    }

    /**
     * @return class-string<Repository<object>>|null
     */
    public function getRepository(): ?string
    {
        return $this->entityAttribute->getRepository();
    }

    /**
     * @return class-string<Factory<object>>|null
     */
    public function getFactory(): ?string
    {
        return $this->entityAttribute->getFactory();
    }

    /**
     * @return Collection<string,ManagedColumnAnnotation<mixed>>
     */
    public function getManagedColumns(): Collection
    {
        return $this->managedColumns;
    }

    /**
     * @return Collection<string,SoftDeleteAnnotation>
     */
    public function getSoftDeletes(): Collection
    {
        return $this->softDeletes;
    }

    private function setId(string $property, Id $attribute): void
    {
        if (isset($this->id)) {
            throw new MultipleIdColumnsException();
        }
        $this->id = $property;
        $this->hasAutogeneratedId = $attribute->autogenerated;
        // TODO: what if ID column has no column annotation?
    }

    /**
     * @param  ColumnAnnotation  $column
     * @param  ReflectionProperty  $property
     * @return void
     */
    private function registerMapper(ColumnAnnotation $column, ReflectionProperty $property): void
    {
        $this->mappers->put(
            $property->getName(),
            $column->getMapper($property)
        );
    }

    /**
     * @param  RelationAnnotation<T,object>  $relationAnnotation
     * @param  ReflectionProperty  $property
     * @return void
     */
    private function registerLinker(RelationAnnotation $relationAnnotation, ReflectionProperty $property): void
    {
        $propertyName = $property->getName();
        /** @var class-string $propertyType */
        $propertyType = $this->getPropertyType($propertyName);
        $this->linkers->put(
            $propertyName,
            $relationAnnotation->getLinker($propertyName, $propertyType, $this->class)
        );
    }

    public function allowsNull(string $property): bool
    {
        return ColumnPropertyService::allowsNull($this->reflectionClass->getProperty($property));
    }

    public function getPropertyType(string $property): string
    {
        return ColumnPropertyService::getPropertyType($this->reflectionClass->getProperty($property));
    }

    public function getDefaultValue(string $property): mixed
    {
        return ColumnPropertyService::getDefaultValue($this->reflectionClass->getProperty($property));
    }

    /**
     * @return bool
     */
    public function hasAutogeneratedId(): bool
    {
        return $this->hasAutogeneratedId;
    }
}
