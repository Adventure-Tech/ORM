<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\WhereColumn;
use Illuminate\Support\Facades\DB;

test('', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('other_column', 'root')
        ->andReturn('qualified.other_column');

    $filter = new WhereColumn('column', IS::NOT_EQUAL, 'other_column');

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where "qualified"."column" != "qualified"."other_column"');
});
