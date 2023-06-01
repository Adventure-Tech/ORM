<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;

test('Can get the aliased table name', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getAliasedTableName')
        ->with('foo')
        ->andReturn('bar');
    $manager = new LocalAliasingManager($mock, 'foo');
    expect($manager->getAliasedTableName())->toBe('bar');
});


test('Can get correctly qualified column names', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'foo')
        ->andReturn('bar');
    $manager = new LocalAliasingManager($mock, 'foo');
    expect($manager->getQualifiedColumnName('column'))->toBe('bar');
});

test('Can get individual column names for select clause', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getSelectedColumnName')
        ->with('column', 'foo')
        ->andReturn('bar');
    $manager = new LocalAliasingManager($mock, 'foo');
    expect($manager->getSelectedColumnName('column'))->toBe('bar');
});
