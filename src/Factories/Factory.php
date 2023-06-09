<?php

namespace AdventureTech\ORM\Factories;

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Persistence\PersistenceManager;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * @template T of object
 */
class Factory
{
    protected readonly Generator $faker;
    /**
     * @var array<string,mixed>
     */
    private array $state = [];

    /**
     * @template E of object
     * @param  class-string<E>  $class
     *
     * @return Factory<E>
     */
    public static function new(string $class): Factory
    {
        $entityReflection = EntityReflection::new($class);
        $factory = $entityReflection->getFactory() ?? self::class;
        return new $factory($entityReflection);
    }

    /**
     * @param  EntityReflection<T>  $entityReflection
     */
    private function __construct(
        private readonly EntityReflection $entityReflection
    ) {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @param  array<string,mixed>  $state
     * @return T
     */
    public function create(array $state = []): object
    {

        $entity = $this->createEntity($state);
        $this->insert($entity);
        return $entity;
    }

    /**
     * @param  int  $count
     * @return Collection<int,T>
     */
    public function createMultiple(int $count): Collection
    {
        return Collection::times($count)->map(fn($_) => $this->create());
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

    private function defaults(string $type): mixed
    {
        return match ($type) {
            'int' => $this->faker->randomNumber(),
            'float' => $this->faker->randomFloat(),
            'string' => $this->faker->word(),
            CarbonImmutable::class => CarbonImmutable::parse($this->faker->dateTime()),
            'array' => [],
            default => null
        };
    }

    /**
     * @param  T  $entity
     * @return void
     */
    private function insert(object $entity): void
    {
        // some reflection dark magic, but it's okay as factories are for test only
        /** @var PersistenceManager<T> $persistenceManager */
        $persistenceManager = new class extends PersistenceManager {
            public function __construct()
            {
            }
        };
        // TODO: handle reflection exceptions
        $refProperty = (new ReflectionClass($persistenceManager))->getProperty('entity');
        $refProperty->setValue($persistenceManager, $this->entityReflection->getClass());

        $persistenceManager::insert($entity);
    }

    /**
     * @param  array<string,mixed>  $state
     * @return void
     */
    private function addMissingProperties(array &$state): void
    {
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if ($property !== $this->entityReflection->getId() && !key_exists($property, $state)) {
                $state[$property] = $this->defaults($mapper->getPropertyType());
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
                $state[$property] = Factory::new($linker->getTargetEntity());
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
}
