<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Repository\Filters\AndWhere;
use AdventureTech\ORM\Repository\Filters\Filter;
use AdventureTech\ORM\Repository\Filters\OrWhere;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

test('Filter gets applied correctly', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('column', 'root')
        ->andReturn('qualified.column');

    $filter = new AndWhere(
        new class implements Filter {
            public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
            {
                $query->where('foo_column', '=', 'foo_value');
            }
        },
        new class implements Filter {
            public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
            {
                $query->where('bar_column', '=', 'bar_value');
            }
        }
    );

    $query = DB::query();

    $filter->applyFilter($query, new LocalAliasingManager($mock, 'root'));
    expect($query)
        ->toSql()->toBe('select * where "foo_column" = ? and "bar_column" = ?')
        ->getBindings()->toEqualCanonicalizing(['foo_value', 'bar_value']);
});
