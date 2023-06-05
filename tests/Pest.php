<?php

use AdventureTech\ORM\Tests\TestCase;

function getProperty(object $object, string $property)
{
    return (new RefLectionClass($object))->getProperty($property)->getValue($object);
}

uses(TestCase::class)->in('Unit', 'Feature', 'Architecture');
