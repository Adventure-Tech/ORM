<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\WhereIn;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Filter gets applied correctly', function (array $value, array $expected) {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');

    $filter = new WhereIn('column', $value);

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where "qualified"."column" in (?, ?)')
        ->getBindings()->toEqualCanonicalizing($expected);
})->with([
    [
        [ 'value_a', 'value_b' ],
        [ 'value_a', 'value_b' ],
    ],
    [
        [ Carbon::parse('2021-01-01 12:00+01'), CarbonImmutable::parse('2023-01-01 12:00:55.99') ],
        [ '2021-01-01T12:00:00+01:00', '2023-01-01T12:00:55+00:00' ],
    ],
    [
        [ Carbon::parse('2021-01-31 12:00-01'), true ],
        [ '2021-01-31T12:00:00-01:00', true ],
    ],
]);
