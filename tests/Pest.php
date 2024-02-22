<?php

use AdventureTech\ORM\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

function getProperty(object $object, string $property)
{
    return (new RefLectionClass($object))->getProperty($property)->getValue($object);
}

uses(TestCase::class, RefreshDatabase::class)->in('Unit', 'Feature');
