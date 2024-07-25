<?php

namespace AdventureTech\ORM\Factories;

use AdventureTech\ORM\ColumnPropertyService;
use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\FactoryException;
use AdventureTech\ORM\Mapping\Linkers\PivotLinker;
use AdventureTech\ORM\Persistence\Persistors\AttachPersistor;
use AdventureTech\ORM\Persistence\Persistors\InsertPersistor;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use ReflectionException;
use ReflectionProperty;

/**
 * @template TEntity of object
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
     * @var array<string,array<int,array<int,Factory<object>|string>>>
     */
    private array $with = [];

    /**
     * @template E of object
     * @param  class-string<E>  $class
     * @return Factory<E>
     */
    public static function new(string $class): Factory
    {
        $entityReflection = EntityReflection::new($class);
        $factory = $entityReflection->getFactory() ?? __CLASS__;
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
        // @codeCoverageIgnoreStart
        if (isset($class)) {
            unset(self::$fakers[$class]);
        } else {
            self::$fakers = [];
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  EntityReflection<TEntity>  $entityReflection
     * @param  Generator  $faker
     */
    protected function __construct(
        private readonly EntityReflection $entityReflection,
        protected readonly Generator $faker
    ) {
        /** @var array<mixed,string> $providers */
        $providers = config('orm.factory.providers', []);
        foreach ($providers as $provider) {
            $this->faker->addProvider(new $provider($this->faker));
        }
    }

    /**
     * @param  array<string,mixed>  $state
     * @return TEntity
     */
    public function create(array $state = []): object
    {
        $entity = $this->make($state);
        $this->insertViaPersistenceManager($entity);
        $this->createHasEntities($entity);
        return $entity;
    }

    /**
     *
     * @param  array<string,mixed>  $state
     * @return TEntity
     */
    public function make(array $state = []): object
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

    /**
     * @param  int  $count
     * @return Collection<int|string,TEntity>
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
     * @param  string  $reverseRelation
     * @param  Factory<object>|null  $factory
     * @return static
     */
    public function with(string $relation, string $reverseRelation, Factory $factory = null): static
    {
        if (!isset($factory)) {
            $linker = $this->entityReflection->getLinker($relation);
            $factory = self::new($linker->getTargetEntity());
            $factory->entityReflection->getLinker($reverseRelation); // checks existence of $reverseRelation
        }
        $this->with[$relation][] = [$factory, $reverseRelation];
        return $this;
    }

    public function without(string $relation): static
    {
        unset($this->with[$relation]);
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
     * @throws ReflectionException
     */
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

        if (ColumnPropertyService::isEnum(new ReflectionProperty($this->entityReflection->getClass(), $property))) {
            return $this->faker->randomElement($type::cases());
        }
        return match ($type) {
            'int' => $this->faker->randomNumber(),
            'float' => $this->faker->randomFloat(),
            'string' => $this->faker->word(),
            'bool' => $this->faker->randomElement([true, false]),
            CarbonImmutable::class => CarbonImmutable::parse($this->faker->dateTime()),
            'array' => [],
            default => throw new FactoryException('No default for type "' . $type . '" of property "' . $property . '". Make sure to register a value in a custom factory class.')
        };
    }

    /**
     * @param  array<string,mixed>  $state
     * @return void
     * @throws ReflectionException
     */
    private function addMissingProperties(array &$state): void
    {
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if (!array_key_exists($property, $state) && $property !== $this->entityReflection->getIdProperty()) {
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
        foreach ($this->entityReflection->getOwningLinkers() as $property => $linker) {
            if (!array_key_exists($property, $state)) {
                $state[$property] = $this->entityReflection->allowsNull($property)
                    ? null
                    : self::new($linker->getTargetEntity());
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
            if ($item instanceof self) {
                $state[$property] = $item->create();
            }
        }
    }

    /**
     * @param  TEntity  $entity
     */
    protected function createHasEntities(object $entity): void
    {
        foreach ($this->with as $relation => $items) {
            $linkedEntities = [];
            /** @var Factory<object> $factory */
            /** @var string $reverseRelation */
            foreach ($items as [$factory, $reverseRelation]) {
                $linker = $this->entityReflection->getLinker($relation);
                if ($linker instanceof PivotLinker) {
                    $linkedEntity = $factory->create();
                    $linkedEntities[] = $linkedEntity;
                } else {
                    $linkedEntity = $factory->create([$reverseRelation => $entity]);
                }
                $linker->link($entity, $linkedEntity);
            }
            if (count($linkedEntities) > 0) {
                $this->attachViaPersistenceManager($entity, $linkedEntities, $relation);
            }
        }
    }

    /**
     * @param TEntity $entity
     * @return void
     */
    protected function insertViaPersistenceManager(object $entity): void
    {
        (new InsertPersistor($this->entityReflection->getClass()))
            ->add($entity, [])
            ->persist();
    }

    /**
     * @param  TEntity  $entity
     * @param array<int|string,object> $linkedEntities
     * @param string $relation
     * @return void
     */
    protected function attachViaPersistenceManager(object $entity, array $linkedEntities, string $relation): void
    {
        (new AttachPersistor($this->entityReflection->getClass()))
            ->add($entity, [$linkedEntities, $relation])
            ->persist();
    }
}
