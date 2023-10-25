<?php

namespace AdventureTech\ORM\Persistence;

use Closure;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;

final class Transaction
{
    public static function wrap(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    public static function begin(): void
    {
        DB::beginTransaction();
    }

    public static function commit(): void
    {
        DB::commit();
    }

    public static function rollback(?int $toLevel = null): void
    {
        DB::rollBack();
    }

    public static function level(): int
    {
        return DB::transactionLevel();
    }

    public static function setManager(DatabaseTransactionsManager $manager): void
    {
        DB::setTransactionManager($manager);
    }

    public static function unsetManager(): void
    {
        DB::unsetTransactionManager();
    }

    private function __construct()
    {
    }
}
