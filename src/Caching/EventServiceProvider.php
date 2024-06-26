<?php

namespace AdventureTech\ORM\Caching;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MigrationsEnded::class, static fn() => ColumnTypeCache::flush());
    }
}
