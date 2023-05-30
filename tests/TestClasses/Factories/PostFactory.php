<?php

namespace AdventureTech\ORM\Tests\TestClasses\Factories;

use AdventureTech\ORM\Factories\Factory;

class PostFactory extends Factory
{
    protected function define(): array
    {
        return [
            'content' => $this->faker->paragraph()
        ];
    }
}
