<?php

namespace AdventureTech\ORM\Tests;

use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const TIMEZONE = 'UTC';

    public static function setAppTimezone(string $tz): void
    {
        Config::set('app.timezone', $tz);
        date_default_timezone_set($tz);
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        DB::statement(sprintf("set timezone TO '%s';", self::TIMEZONE));
        self::setAppTimezone(self::TIMEZONE);
    }
}
