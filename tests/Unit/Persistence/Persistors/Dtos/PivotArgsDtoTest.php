<?php

use AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto;
use Illuminate\Support\Collection;

it('parses correctly', function (array $args, Collection $linkedEntities, string $relation) {
    $dto = PivotArgsDto::parse($args);
    expect($dto)
        ->linkedEntities->toEqual($linkedEntities)
        ->relation->toBe($relation);
})->with([
    [[[1,2,3], 'relation'], collect([1,2,3]), 'relation'],
    [[collect([1,2,3]), 'relation'], collect([1,2,3]), 'relation'],
    [['first' => [1,2,3], 'relation'], collect([1,2,3]), 'relation'],
    [[[1,2,3], 'second' => 'relation'], collect([1,2,3]), 'relation'],
    [['first' => [1,2,3], 'second' => 'relation'], collect([1,2,3]), 'relation'],
]);

it('throws type error if args is not of the expected format', function (?array $args, string $message) {
    expect(static fn() => PivotArgsDto::parse($args))->toThrow(TypeError::class, $message);
})->with([
    [null, 'AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto::parse(): Argument #1 ($args) must be of type array, null given.'],
    [[0], 'AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto::parse(): Argument #1 ($args) must be array of length 2, array of length 1 given.'],
    [[1,2], 'AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto::parse(): First item of argument #1 ($args[0]) must be of iterable, int given.'],
    [[[], 3], 'AdventureTech\ORM\Persistence\Persistors\Dtos\PivotArgsDto::parse(): Second item of argument #1 ($args[1]) must be of type string, int given.'],
]);
