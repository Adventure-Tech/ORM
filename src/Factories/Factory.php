<?php

namespace AdventureTech\ORM\Factories;

use AdventureTech\ORM\ColumnPropertyService;
use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use AdventureTech\ORM\Persistence\PersistenceManager;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use ReflectionProperty;

/**
 * @template T of object
 */
class Factory
{
    /**
     * @var array<class-string,Generator>
     */
    private static array $fakers = [];

    /**
     * @var array<string,mixed>
     */
    private array $state = [];

    /**
     * @var array<string,array<int,Factory<object>>>
     */
    private array $hasFactories = [];
    /**
     * @var array<string,array<int,string>>
     */
    private mixed $hasReverseRelations = [];

    /**
     * @template E of object
     * @param  class-string<E>  $class
     * @return Factory<E>
     */
    public static function new(string $class): Factory
    {
        $entityReflection = EntityReflection::new($class);
        $factory = $entityReflection->getFactory() ?? Factory::class;
        if (!isset(self::$fakers[$class])) {
            self::$fakers[$class] = \Faker\Factory::create(App::currentLocale());
        }
        return new $factory($entityReflection, self::$fakers[$class]);
    }

    /**
     * @template E of object
     * @param  class-string<E>|null  $class
     * @return void
     */
    public static function resetFakers(string $class = null): void
    {
        if (isset($class)) {
            // @codeCoverageIgnoreStart
            unset(self::$fakers[$class]);
            // @codeCoverageIgnoreEnd
        } else {
            self::$fakers = [];
        }
    }

    /**
     * @param  EntityReflection<T>  $entityReflection
     * @param  Generator  $faker
     */
    protected function __construct(
        private readonly EntityReflection $entityReflection,
        protected readonly Generator $faker
    ) {
    }

    /**
     * @param  array<string,mixed>  $state
     * @return T
     */
    public function create(array $state = []): object
    {
        $entity = $this->createEntity($state);
        $this->getPersistenceManager()->insert($entity);
        $this->createdHasEntities($entity);
        return $entity;
    }

    /**
     *
     * @param  array<string,mixed>  $state
     * @return T
     */
    public function make(array $state = []): object
    {
        return $this->createEntity($state);
    }

    /**
     * @param  int  $count
     * @return Collection<int,T>
     */
    public function createMultiple(int $count): Collection
    {
        $collection = Collection::empty();
        for ($i = 0; $i < $count; $i++) {
            $collection->put($i, $this->create());
        }
        return $collection;
    }

    /**
     * @param  array<string,mixed>  $state
     */
    public function state(array $state = []): static
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param  string  $relation
     * @param  Factory<object>|null  $factory
     */
    public function has(string $relation, string $reverseRelation, Factory $factory = null): static
    {
        if (!isset($factory)) {
            $linkers = $this->entityReflection->getLinkers();
            if (!$linkers->has($relation)) {
                throw new InvalidRelationException('Invalid relation used in "has" method [' . $relation . ']');
            }
            $factory = Factory::new($linkers[$relation]->getTargetEntity());
            if (!$factory->entityReflection->getLinkers()->has($reverseRelation)) {
                throw new InvalidRelationException('Invalid reverse relation used in "has" method [' . $reverseRelation . ']');
            }
        }
        // always incrementing arrays at same time => can rely on indexes to be synced
        $this->hasFactories[$relation][] = $factory;
        $this->hasReverseRelations[$relation][] = $reverseRelation;
        return $this;
    }

    public function without(string $relation): static
    {
        unset($this->hasFactories[$relation]);
        unset($this->hasReverseRelations[$relation]);
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    protected function define(): array
    {
        return [];
    }

    /**
     * @param  array<string,mixed>  $state
     * @return T
     */
    private function createEntity(array $state): object
    {
        $state = array_merge($this->define(), $this->state, $state);
        $this->addMissingProperties($state);
        $this->addMissingLinkedFactories($state);
        $this->evaluateLinkedFactories($state);

        $entity = $this->entityReflection->newInstance();
        foreach ($state as $property => $value) {
            EntityAccessorService::set($entity, $property, $value);
        }
        return $entity;
    }

    private function defaults(string $property): mixed
    {
        $default = $this->entityReflection->getDefaultValue($property);
        if (!is_null($default)) {
            return $default;
        }
        if ($this->entityReflection->allowsNull($property)) {
            return null;
        }

        $type = $this->entityReflection->getPropertyType($property);

        $property = new ReflectionProperty($this->entityReflection->getClass(), $property);
        if (ColumnPropertyService::isEnum($property)) {
            return $this->faker->randomElement($type::cases());
        }
        return match ($type) {
            'int' => $this->faker->randomNumber(),
            'float' => $this->faker->randomFloat(),
            'string' => $this->faker->word(),
            'bool' => $this->faker->randomElement([true, false]),
            CarbonImmutable::class => CarbonImmutable::parse($this->faker->dateTime()),
            'array' => [],
            // TODO: throw exception instead of null here:
            default => null
        };
    }

    /**
     * @param  array<string,mixed>  $state
     * @return void
     */
    private function addMissingProperties(array &$state): void
    {
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if ($property !== $this->entityReflection->getIdProperty() && !key_exists($property, $state)) {
                $state[$property] = $this->defaults($property);
            }
        }
    }

    /**
     * @param  array<string,mixed>  $state
     * @return void
     */
    private function addMissingLinkedFactories(array &$state): void
    {
        foreach ($this->entityReflection->getLinkers() as $property => $linker) {
            if ($linker instanceof OwningLinker && !key_exists($property, $state)) {
                $state[$property] = $this->entityReflection->allowsNull($property)
                    ? null
                    : Factory::new($linker->getTargetEntity());
            }
        }
    }

    /**
     * @param  array<string,mixed>  $state
     * @return void
     */
    private function evaluateLinkedFactories(array &$state): void
    {
        foreach ($state as $property => $item) {
            if ($item instanceof Factory) {
                $state[$property] = $item->create();
            }
        }
    }

    /**
     * @param  T  $entity
     */
    private function createdHasEntities(object $entity): void
    {
        foreach ($this->hasFactories as $relation => $factories) {
            $toBeAttached = [];
            foreach ($factories as $index => $factory) {
                $linker = $this->entityReflection->getLinkers()[$relation];
                if ($linker instanceof PivotLinker) {
                    $toBeAttached[] = $factory->create();
                } else {
                    $reverseRelation = $this->hasReverseRelations[$relation][$index];
                    $linkedEntity = $factory->create([
                        $reverseRelation => $entity
                    ]);
                    $linker->link($entity, $linkedEntity);
                }
            }
            if (count($toBeAttached) > 0) {
                $this->getPersistenceManager()->attach($entity, $toBeAttached, $relation);
            }
        }
    }

    /**
     * @return PersistenceManager<T>
     */
    private function getPersistenceManager(): PersistenceManager
    {
        // some reflection dark magic, but it's okay as factories are for test only
        /** @var PersistenceManager<T> $persistenceManager */
        $persistenceManager = new class ($this->entityReflection->getClass()) extends PersistenceManager {
            protected static string $entity;

            /**
             * @param  class-string<T>  $entityClassName
             */
            public function __construct(string $entityClassName)
            {
                self::$entity = $entityClassName;
            }
        };
        return $persistenceManager;
    }
}
