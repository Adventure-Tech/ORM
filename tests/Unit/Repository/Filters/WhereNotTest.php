<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\WhereNot;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Filter gets applied correctly', function (mixed $value, string $expected) {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');

    $filter = new WhereNot('column', IS::EQUAL, $value);

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where not "qualified"."column" = ?')
        ->getBindings()->toEqualCanonicalizing([$expected]);
})->with([
    ['value', 'value'],
    [CarbonImmutable::parse('2023-01-01 12:00+01:00'), '2023-01-01T12:00:00+01:00'],
    [CarbonImmutable::parse('2023-01-01T12:00:53.99-01:00'), '2023-01-01T12:00:53-01:00'],
    [CarbonImmutable::parse('2023-01-01 12:00'), '2023-01-01T12:00:00+00:00'],
    [Carbon::parse('2023-01-01 12:00+01:00'), '2023-01-01T12:00:00+01:00'],
    [Carbon::parse('2023-01-01T12:00:53.99-01:00'), '2023-01-01T12:00:53-01:00'],
    [Carbon::parse('2023-01-01 12:00'), '2023-01-01T12:00:00+00:00'],
]);
