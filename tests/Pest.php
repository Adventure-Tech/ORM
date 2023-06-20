<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestCase;

function getProperty(object $object, string $property)
{
    return (new RefLectionClass($object))->getProperty($property)->getValue($object);
}

uses(TestCase::class)
    ->beforeEach(fn() => Factory::resetFakers())
    ->in('Unit', 'Feature');
