<?php

use AdventureTech\ORM\Tests\TestCase;

function getProperty(object $object, string $property)
{
    $reflection = new RefLectionClass($object);
    $reflection = $reflection->getProperty($property);
    $reflection->setAccessible(true);
    return $reflection->getValue($object);
}

uses(TestCase::class)->in('Unit', 'Feature', 'Architecture');
