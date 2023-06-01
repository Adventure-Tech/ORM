<?php

use AdventureTech\ORM\Exceptions\InvalidTypeException;
use AdventureTech\ORM\Mapping\ManagedColumns\UpdatedAt;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

test('Insert value is generated correctly', function () {
    $instance = new UpdatedAt();
    $now = CarbonImmutable::now();
    expect($instance->getInsertValue()->toIso8601String())->toBe($now->toIso8601String());
});

test('Update value is generated correctly', function (mixed $value) {
    $instance = new UpdatedAt();
    $now = CarbonImmutable::now();
    expect($instance->getUpdateValue($value)->toIso8601String())->toBe($now->toIso8601String());
})->with([
    null,
    'string',
    123,
    Carbon::now(),
    CarbonImmutable::now(),
    CarbonImmutable::now()->subDay()
]);

test('Delete value is generated correctly', function () {
    $instance = new UpdatedAt();
    expect($instance->getDeleteValue())->toBeNull();
});
