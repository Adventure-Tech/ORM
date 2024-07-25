<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;

class CustomProvider extends \Faker\Provider\Base
{
    public static $count = 0;
    public function custom(): string
    {
        self::$count++;
        return 'not random';
    }
}

#[Entity(factory: FooFactory::class)]
class FooEntity
{
    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $custom;
}

class FooFactory extends Factory
{
    protected function define(): array
    {
        return [
            'custom' => $this->faker->custom,
        ];
    }
}

test('Can register custom providers', function () {
    config()->set('orm.factory.providers', [CustomProvider::class]);
    $entity = Factory::new(FooEntity::class)->make();
    expect($entity->custom)->toBe('not random')
        ->and(CustomProvider::$count)->toBe(1);
});
