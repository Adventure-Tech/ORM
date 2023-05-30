<?php

namespace AdventureTech\ORM\Factories;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use AdventureTech\ORM\Persistence\BasePersistenceManager;
use Carbon\CarbonImmutable;
use Faker\Generator;

class Factory extends BasePersistenceManager
{
    protected string $entity;
    protected Generator $faker;
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
        return new $factory($class);
    }

    private function __construct(string $class)
    {
        $this->entity = $class;
        $this->faker = \Faker\Factory::create();
        parent::__construct();
    }


    public function create(array $state = [])
    {
        $entity = $this->entityReflection->newInstance();

        $state = array_merge($this->define(), $state);

        foreach ($this->entityReflection->getLinkers() as $property => $linker) {
            if (key_exists($property, $state)) {
                $entity->{$property} = $state[$property];
            } elseif ($linker instanceof BelongsToLinker) {
                $entity->{$property} = (new Factory($linker->getTargetEntity()))->create();
            }
        }
        foreach ($this->entityReflection->getMappers() as $property => $mapper) {
            if (key_exists($property, $state)) {
                $entity->{$property} = $state[$property];
            } elseif ($property !== $this->entityReflection->getId()) {
                $entity->{$property} = $this->defaults($mapper->getType());
            }
        }
        $this->insert($entity);
        return $entity;
    }

    protected function defaults(string $type): mixed
    {
        return match ($type) {
            'int' => $this->faker->numberBetween(),
            'string' => $this->faker->word(),
            CarbonImmutable::class => CarbonImmutable::parse($this->faker->dateTime()),
            'array' => [],
            default => null
        };
    }

    protected function define(): array
    {
        return [];
    }
}
