<?php

namespace AdventureTech\ORM\Mapping\Relations;

use Illuminate\Database\Query\Builder;

/**
 * @template FROM of object
 * @template TO of object
 */
interface Relation
{
    /**
     * @param  string  $propertyName
     * @param  class-string<TO>  $propertyType
     * @param  class-string<FROM>  $className
     * @return void
     */
    public function initialize(
        string $propertyName,
        string $propertyType,
        string $className
    ): void;

    /**
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function join(Builder $query, string $from, string $to): void;

    /**
     * @return class-string<TO>
     */
    public function getTargetEntity(): string;

    /**
     * @param  FROM  $currentEntity
     * @param  TO|null  $relatedEntity
     * @return void
     */
    public function link(object $currentEntity, ?object $relatedEntity): void;
}
