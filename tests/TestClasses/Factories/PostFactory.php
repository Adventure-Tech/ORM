<?php

namespace AdventureTech\ORM\Tests\TestClasses\Factories;

use AdventureTech\ORM\Factories\Factory;

class PostFactory extends Factory
{
    protected function define(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'content' => $this->faker->paragraph()
        ];
    }
}
