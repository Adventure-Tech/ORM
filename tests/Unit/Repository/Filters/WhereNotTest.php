<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\WhereNot;
use Illuminate\Support\Facades\DB;

test('', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');

    $filter = new WhereNot('column', IS::EQUAL, 'value');

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where not "qualified"."column" = ?')
        ->getBindings()->toEqualCanonicalizing(['value']);
});
