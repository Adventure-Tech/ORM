<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestCase;
use AdventureTech\ORM\Tests\TestClasses\Entities\TimezoneEntity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Retrieving entities via Repository handles timezones correctly', function (
    string $appTimezone,
    string $dbTimezone,
    array $withoutTz,
    array $withTz,
) {
    TestCase::setAppTimezone($appTimezone);
    DB::statement("set timezone TO '$dbTimezone';");
    $id = DB::table('timezone_entities')->insertGetId([
        'datetime_without_tz' => $withoutTz['datetime'],
        'datetime_with_tz' => $withTz['datetime'],
        'timezone' => $withTz['timezone'],
    ]);

    $entity = Repository::new(TimezoneEntity::class)->find($id);

    expect($entity)
        ->datetimeWithoutTz->toIso8601String()->toBe($withoutTz['expected'])
        ->datetimeWithTz->toIso8601String()->toBe($withTz['expected']);
})->with([
    'both UTC' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+00 (interpreted by DB in its local time)
            'expected' => '2020-01-01T12:00:00+00:00', // 12:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00:00', // 12:00+00
            'timezone' => '+02:00',
            'expected' => '2020-01-01T14:00:00+02:00', // 12:00+00
        ],
    ],
    'non-UTC app' => [
        'appTimezone' => 'Europe/Athens', // corresponds to +02:00 on the 2020-01-01 (winter time)
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+00 (interpreted by DB in its local time)
            'expected' => '2020-01-01T14:00:00+02:00', // 14:00+02
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00:00', // 12:00+00
            'timezone' => '+01:00',
            'expected' => '2020-01-01T13:00:00+01:00', // 13:00+01
        ],
    ],
    'non-UTC database' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'Europe/Oslo', // corresponds to +01:00 on the 2020-01-01 (winter time)
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+01 (interpreted by DB in its local time)
            'expected' => '2020-01-01T11:00:00+00:00', // 11:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+01 (interpreted by DB in its local time)
            'timezone' => '+02:00',
            'expected' => '2020-01-01T13:00:00+02:00', // 13:00+02
        ],
    ],
]);

test('Storing entities via PersistenceManagers handles timezones correctly', function (
    string $appTimezone,
    string $dbTimezone,
    array $withoutTz,
    array $withTz,
) {
    TestCase::setAppTimezone($appTimezone);
    DB::statement("set timezone TO '$dbTimezone';");
    $id = Factory::new(TimezoneEntity::class)->create([
        'datetimeWithoutTz' => CarbonImmutable::parse($withoutTz['datetime']),
        'datetimeWithTz' => CarbonImmutable::parse($withTz['datetime'])->setTimezone($withTz['timezone']),
    ])->id;

    $record = DB::table('timezone_entities')->find($id);

    expect($record)
        ->datetime_without_tz->toBe($withoutTz['expected'])
        ->datetime_with_tz->toBe($withTz['expected'][0])
        ->timezone->toBe($withTz['expected'][1]);
})->with([
    'both UTC' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',       // 12:00+00 (interpreted by APP in its local time)
            'expected' => '2020-01-01 12:00:00+00', // 12:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00',             // 12:00+00
            'timezone' => '+02:00',
            'expected' => ['2020-01-01 12:00:00+00', '+02:00'], // 14:00+02
        ],
    ],
    'non-UTC app' => [
        'appTimezone' => 'Europe/Athens', // corresponds to +02:00 on the 2020-01-01 (winter time)
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',                   // 12:00+02 (interpreted by APP in its local time)
            'expected' => '2020-01-01 10:00:00+00',             // 10:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00',             // 12:00+00
            'timezone' => '+01:00',
            'expected' => ['2020-01-01 12:00:00+00', '+01:00'], // 13:00+01
        ],
    ],
    'non-UTC database' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'Europe/Oslo', // corresponds to +01:00 on the 2020-01-01 (winter time)
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',                   // 12:00+00 (interpreted by APP in its local time)
            'expected' => '2020-01-01 13:00:00+01',             // 13:00+01
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00',                   // 12:00+00 (interpreted by APP in its local time)
            'timezone' => '+02:00',
            'expected' => ['2020-01-01 13:00:00+01', '+02:00'], // 14:00+02
        ],
    ],
]);

test('Storing and then reading handles timezones correctly', function (
    string $appTimezone,
    string $dbTimezone,
    array $withoutTz,
    array $withTz,
) {
    TestCase::setAppTimezone($appTimezone);
    DB::statement("set timezone TO '$dbTimezone';");
    $id = Factory::new(TimezoneEntity::class)->create([
        'datetimeWithoutTz' => CarbonImmutable::parse($withoutTz['datetime']),
        'datetimeWithTz' => CarbonImmutable::parse($withTz['datetime'])->setTimezone($withTz['timezone']),
    ])->id;

    $entity = Repository::new(TimezoneEntity::class)->find($id);

    expect($entity)
        ->datetimeWithoutTz->toIso8601String()->toBe($withoutTz['expected'])
        ->datetimeWithTz->toIso8601String()->toBe($withTz['expected']);
})->with([
    'both UTC' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+00 (interpreted by APP in its local time)
            'expected' => '2020-01-01T12:00:00+00:00', // 12:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00:00', // 12:00+00
            'timezone' => '+02:00',
            'expected' => '2020-01-01T14:00:00+02:00', // 12:00+00
        ],
    ],
    'non-UTC app' => [
        'appTimezone' => 'Europe/Athens', // corresponds to +02:00 on the 2020-01-01 (winter time)
        'dbTimezone' => 'UTC',
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+02 (interpreted by APP in its local time)
            'expected' => '2020-01-01T12:00:00+02:00', // 14:00+02
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00:00+00:00', // 12:00+00
            'timezone' => '+01:00',
            'expected' => '2020-01-01T13:00:00+01:00', // 13:00+01
        ],
    ],
    'non-UTC database' => [
        'appTimezone' => 'UTC',
        'dbTimezone' => 'Europe/Oslo', // corresponds to +01:00 on the 2020-01-01 (winter time)
        'withoutTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+00 (interpreted by APP in its local time)
            'expected' => '2020-01-01T12:00:00+00:00', // 12:00+00
        ],
        'withTz' => [
            'datetime' => '2020-01-01 12:00',          // 12:00+00 (interpreted by APP in its local time)
            'timezone' => '+02:00',
            'expected' => '2020-01-01T14:00:00+02:00', // 14:00+02
        ],
    ],
]);
