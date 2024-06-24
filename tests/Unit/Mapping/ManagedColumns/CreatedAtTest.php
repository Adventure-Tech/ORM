<?php

use AdventureTech\ORM\Mapping\ManagedColumns\CreatedAt;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

test('Insert value is generated correctly', function () {
    $instance = new CreatedAt();
    $now = CarbonImmutable::now();
    expect($instance->getInsertValue()->toIso8601String())->toBe($now->toIso8601String());
});

test('Update value is generated correctly as null', function () {
    $instance = new CreatedAt();
    expect($instance->getUpdateValue())->toBeNull();
});

test('Delete value is generated correctly', function () {
    $instance = new CreatedAt();
    expect($instance->getDeleteValue())->toBeNull();
});
