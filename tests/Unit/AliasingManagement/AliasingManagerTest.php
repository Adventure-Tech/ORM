<?php

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\Exceptions\AliasingException;

test('Can get the aliased table name', function (AliasingManager $manager, string $expected, string $localRoot) {
    expect($manager->getAliasedTableName($localRoot))->toBe($expected);
})->with('manager')->with([
    ['foo', 'foo'],
    ['_0_', 'foo/bar'],
    ['_1_', 'foo/bar/baz'],
    ['_2_', 'foo/bam'],
]);

test('Can get columns for the select clause', function (AliasingManager $manager) {
    expect($manager->getSelectColumns())
        ->toBeArray()
        ->toEqualCanonicalizing([
            'foo.foo_a as foofoo_a',
            'foo.foo_b as foofoo_b',
            '_0_.bar_a as _0_bar_a',
            '_0_.bar_b as _0_bar_b',
            '_1_.baz_a as _1_baz_a',
            '_1_.baz_b as _1_baz_b',
            '_2_.bam_a as _2_bam_a',
            '_2_.bam_b as _2_bam_b',
        ]);
})->with('manager');

test('Can get correctly qualified column names', function (AliasingManager $manager, string $expected, string $columnExpression, string $localRoot) {
    expect($manager->getQualifiedColumnName($columnExpression, $localRoot))->toBe($expected);
})->with('manager')->with([
    [ 'foo.foo_a', 'foo_a', 'foo'],
    ['foo.foo_a', '../foo_a', 'foo/bar', ],
    ['foo.foo_a', '../../foo_a', 'foo/bar/baz'],
    ['foo.foo_a', '../foo_a', 'foo/bam'],
    ['_0_.bar_a', 'bar/bar_a', 'foo'],
    ['_0_.bar_a', 'bar_a', 'foo/bar'],
    ['_0_.bar_a', '../bar_a', 'foo/bar/baz'],
    ['_0_.bar_a', '../bar/bar_a', 'foo/bam'],
    ['_1_.baz_a', 'bar/baz/baz_a', 'foo'],
    ['_1_.baz_a' ,'baz/baz_a', 'foo/bar'],
    ['_1_.baz_a' ,'baz_a', 'foo/bar/baz'],
    ['_1_.baz_a' ,'../bar/baz/baz_a', 'foo/bam'],
    ['_2_.bam_a', 'bam/bam_a', 'foo'],
    ['_2_.bam_a' ,'../bam/bam_a', 'foo/bar'],
    ['_2_.bam_a' ,'../../bam/bam_a', 'foo/bar/baz'],
    ['_2_.bam_a' ,'bam_a', 'foo/bam'],
]);

test('Can get individual column names for select clause', function (AliasingManager $manager, string $expected, string $columnExpression, string $localRoot) {
    expect($manager->getSelectedColumnName($columnExpression, $localRoot))->toBe($expected);
})->with('manager')->with([
    [ 'foofoo_a', 'foo_a', 'foo'],
    ['foofoo_a', '../foo_a', 'foo/bar', ],
    ['foofoo_a', '../../foo_a', 'foo/bar/baz'],
    ['foofoo_a', '../foo_a', 'foo/bam'],
    ['_0_bar_a', 'bar/bar_a', 'foo'],
    ['_0_bar_a', 'bar_a', 'foo/bar'],
    ['_0_bar_a', '../bar_a', 'foo/bar/baz'],
    ['_0_bar_a', '../bar/bar_a', 'foo/bam'],
    ['_1_baz_a', 'bar/baz/baz_a', 'foo'],
    ['_1_baz_a' ,'baz/baz_a', 'foo/bar'],
    ['_1_baz_a' ,'baz_a', 'foo/bar/baz'],
    ['_1_baz_a' ,'../bar/baz/baz_a', 'foo/bam'],
    ['_2_bam_a', 'bam/bam_a', 'foo'],
    ['_2_bam_a' ,'../bam/bam_a', 'foo/bar'],
    ['_2_bam_a' ,'../../bam/bam_a', 'foo/bar/baz'],
    ['_2_bam_a' ,'bam_a', 'foo/bam'],
]);

test('Can correctly add relation', function () {
    $manager = new AliasingManager('foo', ['foo_a', 'foo_b']);
    expect(fn () => $manager->getAliasedTableName('foo/bar'))
        ->toThrow(AliasingException::class, 'Failed to resolve key "bar". No keys available.');
    $manager->addRelation('foo/bar', ['bar_a', 'bar_b']);
    expect(fn () => $manager->getAliasedTableName('foo/bar'))
        ->not->toThrow(AliasingException::class, 'Failed to resolve key "bar". No keys available.');
});

dataset('manager', function () {
    $manager = new AliasingManager('foo', ['foo_a', 'foo_b']);
    $manager->addRelation('foo/bar', ['bar_a', 'bar_b']);
    $manager->addRelation('foo/bar/baz', ['baz_a', 'baz_b']);
    $manager->addRelation('foo/bam', ['bam_a', 'bam_b']);
    yield [$manager];
});
