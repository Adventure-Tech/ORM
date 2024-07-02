<?php

namespace AdventureTech\ORM\Caching;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ColumnTypeCache
{
    public static function flush(): void
    {
        Cache::forget(self::getColumnTypesCacheKey());
    }

    /**
     * @return array<string,string>
     */
    public static function get(string $table): array
    {
        /** @var array<string,array<string,string>> $cached */
        $cached =  Cache::get(self::getColumnTypesCacheKey(), []);
        if (!isset($cached[$table])) {
            $cached[$table] = DB::table('information_schema.columns')
                ->select(['column_name', 'data_type'])
                ->where('table_name', $table)
                ->orderBy('ordinal_position')
                ->get()
                ->pluck('data_type', 'column_name')
                ->toArray();
        }
        Cache::set(self::getColumnTypesCacheKey(), $cached);
        /** @var array<string,array<string,string>> $cached */
        return $cached[$table];
    }

    private static function getColumnTypesCacheKey(): string
    {
        return config('orm.cache.key', 'adventure-tech.orm.cache') .  '.column-types';
    }
}
