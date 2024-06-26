<?php

use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Tests\TestClasses\Entities\Account;
use AdventureTech\ORM\Tests\TestClasses\Persistence\AccountPersistence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('Can insert entity with non-autogenerated ID column', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;

    AccountPersistence::insert($account);

    expect($account->uuid)->toBe($uuid)
    ->and(DB::table('es_accounts')->get())
        ->toHaveCount(1)
        ->first()->uuid->toBe($uuid);
});

test('Attempting to insert entity with ID already present on DB leads to exception', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;
    AccountPersistence::insert($account);

    expect(fn() => AccountPersistence::insert($account))->toThrow(UniqueConstraintViolationException::class);
})->skip('DB transaction setup by RefreshDatabase trait breaks if an SQL exception occurs');

test('Not providing value for non-autogenerated ID column leads to exception', function () {
    $account = new Account();
    $account->name = 'cash';
    $account->amount = 0;
    expect(fn() => AccountPersistence::insert($account))->toThrow(
        PersistenceException::class,
        'Must set non-autogenerated ID column when inserting'
    );
});

test('Can update entity with non-autogenerated ID column', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;

    AccountPersistence::insert($account);

    $account->name = 'CASH';
    AccountPersistence::update($account);

    expect(DB::table('es_accounts')->first())
        ->name->toBe('CASH')
        ->uuid->toBe($uuid);
});

test('Must provide correct ID value when updating entity with non-autogenerated ID column', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;

    AccountPersistence::insert($account);

    $account->uuid = 'new-uuid';
    expect(fn()=>AccountPersistence::update($account))->toThrow(
        PersistenceException::class,
        'Could not update all entities. Updated 0 out of 1.'
    );
});

test('Can delete entity with non-autogenerated ID column', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;

    AccountPersistence::insert($account);

    AccountPersistence::delete($account);

    expect(DB::table('es_accounts')->count())->toBe(0);
});

test('Must provide correct ID value when deleting entity with non-autogenerated ID column', function () {
    $uuid = (string) Str::uuid();

    $account = new Account();
    $account->uuid = $uuid;
    $account->name = 'cash';
    $account->amount = 0;

    AccountPersistence::insert($account);

    $account->uuid = 'new-uuid';
    expect(fn() => AccountPersistence::delete($account))->toThrow(
        PersistenceException::class,
        'Could not delete all entities. Deleted 0 out of 1.'
    );
});
