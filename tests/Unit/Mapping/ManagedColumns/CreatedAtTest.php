<?php

use AdventureTech\ORM\Exceptions\InvalidTypeException;
use AdventureTech\ORM\Mapping\ManagedColumns\CreatedAt;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

test('Insert value is generated correctly', function () {
    $instance = new CreatedAt();
    $now = CarbonImmutable::now();
    expect($instance->getInsertValue()->toIso8601String())->toBe($now->toIso8601String());
});

test('Update value is generated correctly', function () {
    $instance = new CreatedAt();
    $now = CarbonImmutable::now();
    expect($instance->getUpdateValue($now))->toBe($now);
});

test('Function to get update value protects against wrong types', function (mixed $value) {
    $instance = new CreatedAt();
    expect(fn() => $instance->getUpdateValue($value))
        ->toThrow(InvalidTypeException::class, 'Wrong type passed to managed column');
})->with([
    null,
    'string',
    123,
    Carbon::now()
]);

test('Delete value is generated correctly', function () {
    $instance = new CreatedAt();
    expect($instance->getDeleteValue())->toBeNull();
});
