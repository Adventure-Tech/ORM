<?php

use AdventureTech\ORM\Caching\ColumnTypeCache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

test('Getting works correctly', function () {
    $dbCount = 0;
    DB::listen(static function () use (&$dbCount) {
        $dbCount++;
    });
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toBeNull()
        ->and($dbCount)->toBe(0)
        ->and(ColumnTypeCache::get('users'))->toEqual([
            'id' => 'int8',
            'name' => 'varchar',
            'favourite_color' => 'varchar',
            'created_at' => 'timestamptz',
            'updated_at' => 'timestamptz',
            'deleted_at' => 'timestamptz',
        ])
        ->and($dbCount)->toBe(1)
        ->and(Cache::get('adventure-tech.orm.cache.column-types'))->toHaveCount(1)->toHaveKey('users')
        ->and(ColumnTypeCache::get('users'))->toEqual([
            'id' => 'int8',
            'name' => 'varchar',
            'favourite_color' => 'varchar',
            'created_at' => 'timestamptz',
            'updated_at' => 'timestamptz',
            'deleted_at' => 'timestamptz',
        ])
        ->and($dbCount)->toBe(1);
});

test('Can flush column type cache', function () {
    ColumnTypeCache::get('users');
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toHaveCount(1);
    ColumnTypeCache::flush();
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toBeNull();
});

test('Can config cache key', function () {
    Config::set('orm.cache.key', 'custom.cache.key');
    ColumnTypeCache::get('users');
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toBeNull()
        ->and(Cache::get('custom.cache.key.column-types'))->toHaveCount(1);
});

test('Migrations flush cache', function () {
    // populate cache
    ColumnTypeCache::get('users');
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toHaveCount(1);

    // dispatch event which should trigger flushing of cache
    app(Dispatcher::class)->dispatch(new MigrationsEnded('method'));

    // assert cache flushed
    expect(Cache::get('adventure-tech.orm.cache.column-types'))->toBeNull();
});
