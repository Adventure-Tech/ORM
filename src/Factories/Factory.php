<?php

namespace AdventureTech\ORM\Factories;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Persistence\PersistenceManager;
use Carbon\CarbonImmutable;
use Faker\Generator;
use ReflectionClass;

/**
 * @template T of object
 */
class Factory
{
    private readonly Generator $faker;

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
        $persistenceManager = self::getPersistenceManager($class);
        return new $factory($entityReflection, $persistenceManager);
    }

    /**
     * @param  EntityReflection<T>  $entityReflection
     * @param  PersistenceManager<T>  $persistenceManager
     */
    private function __construct(private readonly EntityReflection $entityReflection, private readonly PersistenceManager $persistenceManager)
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @template F of object
     * @param  class-string<F>  $class
     * @return PersistenceManager<F>
     */
    private static function getPersistenceManager(string $class): PersistenceManager
    {
        // some reflection dark magic, but it's okay as factories are for test only
        /** @var PersistenceManager<F> $persistenceManager */
        $persistenceManager = new class extends PersistenceManager {
            public function __construct()
            {
            }
        };
        // TODO: handle reflection exceptions
        $refProperty = (new ReflectionClass($persistenceManager))->getProperty('entity');
        $refProperty->setValue($persistenceManager, $class);
        return $persistenceManager;
    }


    /**
     * @param  array<string,mixed>  $state
     * @return T
     */
    public function create(array $state = []): object
    {
        $entity = $this->entityReflection->newInstance();

        $state = array_merge($this->define(), $state);

        foreach ($this->entityReflection->getLinkers() as $property => $linker) {
            if (key_exists($property, $state)) {
                $entity->{$property} = $state[$property];
            } elseif ($linker instanceof OwningLinker) {
                $entity->{$property} = Factory::new($linker->getTargetEntity())->create();
            }
        }
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if (key_exists($property, $state)) {
                $entity->{$property} = $state[$property];
            } elseif ($property !== $this->entityReflection->getId()) {
                $entity->{$property} = $this->defaults($mapper->getPropertyType());
            }
        }
        $this->persistenceManager::insert($entity);
        return $entity;
    }

    protected function defaults(string $type): mixed
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
     * @return array<string,mixed>
     */
    protected function define(): array
    {
        return [];
    }
}
