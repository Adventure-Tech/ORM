<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Linkers\HasManyLinker;
use AdventureTech\ORM\Repository\Filters\Filter;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses()
    ->beforeAll(function () {
        $mock = EntityReflection::fake();
        $mock->shouldReceive('getTableName')->andReturn('table_name');
        $mock->shouldReceive('getId')->andReturn('id_column');
    })
    ->afterAll(function () {
        EntityReflection::resetFake();
    });


test('Linker exposes the target entity name', function () {
    $linker = new HasManyLinker('OriginEntity', 'TargetEntity', 'relation', 'foreign_key');
    expect($linker->getTargetEntity())->toBe('TargetEntity');
});


test('Linker can link entities correctly', function () {
    $linker = new HasManyLinker('OriginEntity', 'TargetEntity', 'relation', 'foreign_key');
    $currentEntity = new stdClass();
    $relatedEntity = new stdClass();
    $linker->link($currentEntity, $relatedEntity);
    expect($currentEntity)->toHaveProperty('relation')
        ->relation->toBeInstanceOf(Collection::class)
        ->relation->toHaveCount(1)
        ->relation->first()->toBe($relatedEntity);
});

test('Linker can link null entity correctly', function () {
    $linker = new HasManyLinker('OriginEntity', 'TargetEntity', 'relation', 'foreign_key');
    $currentEntity = new stdClass();
    $relatedEntity = null;
    $linker->link($currentEntity, $relatedEntity);
    expect($currentEntity)->toHaveProperty('relation')
        ->property->toBeNull();
});

test('Linker can apply join correctly', function (LocalAliasingManager $manager, array $filters, string $expectedQueryString, array $expectedBindings) {
    $linker = new HasManyLinker('OriginEntity', 'TargetEntity', 'relation', 'foreign_key');
    $query = DB::query();
    $linker->join($query, $manager, $manager, $filters);
    expect($query->toSql())->toBe($expectedQueryString)
        ->and($query->getBindings())->toEqualCanonicalizing($expectedBindings);
})
    ->with('aliasing manager')
    ->with([
        [
            [],
            'select * left join "table_name" as "table_alias" on "qualified"."foreign_key" = "qualified"."id"',
            [],
        ],
        [
           [new class implements Filter {
            public function applyFilter(
                JoinClause|Builder $query,
                LocalAliasingManager $aliasingManager
            ): void {
                $query->where('filter_column', '=', 'filter_value');
            }
           }],
            'select * left join "table_name" as "table_alias" on "qualified"."foreign_key" = "qualified"."id" and "filter_column" = ?',
            ['filter_value'],
        ],
    ]);


dataset('aliasing manager', function () {
    $mock = Mockery::mock(AliasingManager::class);
    $mock->shouldReceive('getAliasedTableName')
        ->with('root')
        ->andReturn('table_alias');
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('id_column', 'root')
        ->andReturn('qualified.id');
    $mock->shouldReceive('getQualifiedColumnName')
        ->with('foreign_key', 'root')
        ->andReturn('qualified.foreign_key');
    yield new LocalAliasingManager($mock, 'root');
});
