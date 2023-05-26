<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use Illuminate\Database\Query\Builder;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 */
interface Linker
{
    /**
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function join(Builder $query, string $from, string $to): void;

    /**
     * @return class-string<TARGET>
     */
    public function getTargetEntity(): string;

    /**
     * @param  ORIGIN  $currentEntity
     * @param  TARGET|null  $relatedEntity
     * @return void
     */
    public function link(object $currentEntity, ?object $relatedEntity): void;
}
