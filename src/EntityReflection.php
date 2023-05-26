<?php

namespace AdventureTech\ORM;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\CreatedAt;
use AdventureTech\ORM\Mapping\DeletedAt;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Relations\Relation;
use AdventureTech\ORM\Mapping\UpdatedAt;
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
     * @var Collection<string, Linker<T,object>>
     */
    private Collection $linkers;
    /**
     * @var Entity<T>
     */
    private Entity $entityAttribute;
    private string $id;
    private ?string $createdAt = null, $updatedAt = null, $deletedAt = null;

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
        $this->linkers = Collection::empty();

        // TODO: check that we only set single CreatedAt et
        // TODO: check that CreatedAt mappers only have single column?
        foreach ($this->reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Id) {
                    $this->setId($property->getName());
                } elseif ($attributeInstance instanceof CreatedAt) {
                    $this->createdAt = $property->getName();
                } elseif ($attributeInstance instanceof UpdatedAt) {
                    $this->updatedAt = $property->getName();
                } elseif ($attributeInstance instanceof DeletedAt) {
                    $this->deletedAt = $property->getName();
                } elseif ($attributeInstance instanceof Column) {
                    $this->registerMapper($attributeInstance, $property);
                } elseif ($attributeInstance instanceof Relation) {
                    $this->registerLinker($attributeInstance, $property);
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
     * @return Mapper|null
     */
    public function getCreatedAtMapper(): ?Mapper
    {
        return $this->mappers->get($this->createdAt);
    }

    /**
     * @return Mapper|null
     */
    public function getUpdatedAtMapper(): ?Mapper
    {
        return $this->mappers->get($this->updatedAt);
    }

    /**
     * @return Mapper|null
     */
    public function getDeletedAtMapper(): ?Mapper
    {
        return $this->mappers->get($this->deletedAt);
    }

    private function setId(string $propertyName): void
    {
        if (isset($this->id)) {
            throw new LogicException('Cannot have multiple ID columns');
        }
        $this->id = $propertyName;
    }

    private function setCreatedAt(string $propertyName): void
    {
        $this->createdAt = $this->getManagedDatetimeColumnName($propertyName, 'createdAt');
    }

    private function setUpdatedAt(string $propertyName): void
    {
        $this->updatedAt = $this->getManagedDatetimeColumnName($propertyName, 'updatedAt');
    }

    private function setDeletedAt(string $propertyName): void
    {
        $this->deletedAt = $this->getManagedDatetimeColumnName($propertyName, 'deletedAt');
    }

    private function getManagedDatetimeColumnName(string $propertyName, string $asd): string
    {
//        if (isset($this->{$asd})) {
//            throw new LogicException('Cannot have multiple ' . $asd . ' columns');
//        }
        $columnNames = $this->mappers->get($propertyName)->getColumnNames();
        if (count($columnNames) !== 1) {
            throw new LogicException($asd . ' column can only have single associated column');
        }
        return $columnNames[0];
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
        // TODO: check if `$property->getType()?->getName()` is correct
        $type = $property->getType();
        if (is_null($type)) {
            // TODO: custom exception
            throw new RuntimeException('Encountered null in reflection type');
        }
        $this->linkers->put(
            $property->getName(),
            $relation->getLinker($property->getName(), $type->getName(), $this->class)
        );
    }
}
