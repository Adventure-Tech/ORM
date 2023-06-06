<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\WhereNotIn;
use Illuminate\Support\Facades\DB;

test('Filter gets applied correctly', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');

    $filter = new WhereNotIn('column', ['value_a', 'value_b']);

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where "qualified"."column" not in (?, ?)')
        ->getBindings()->toEqualCanonicalizing(['value_a', 'value_b']);
});
