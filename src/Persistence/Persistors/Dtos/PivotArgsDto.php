<?php

namespace AdventureTech\ORM\Persistence\Persistors\Dtos;

use Illuminate\Support\Collection;
use TypeError;

final readonly class PivotArgsDto
{
    /**
     * @var Collection<int|string,object>
     */
    public Collection $linkedEntities;

    /**
     * @param  iterable<int|string,object>  $linkedEntities
     * @param  string  $relation
     */
    public function __construct(iterable $linkedEntities, public string $relation)
    {
        $this->linkedEntities = Collection::wrap($linkedEntities);
    }

    /**
     * @param  array<int,mixed>|null  $args
     * @return self
     */
    public static function parse(?array $args): self
    {
        if (is_null($args)) {
            throw new TypeError(self::class . '::parse(): Argument #1 ($args) must be of type array, null given.');
        }
        if (count($args) !== 2) {
            throw new TypeError(self::class . '::parse(): Argument #1 ($args) must be array of length 2, array of length ' . count($args) . ' given.');
        }
        $linkedEntities = array_shift($args);
        if (!is_iterable($linkedEntities)) {
            throw new TypeError(self::class . '::parse(): First item of argument #1 ($args[0]) must be of iterable, ' . get_debug_type($linkedEntities) . ' given.');
        }
        $relation = array_shift($args);
        if (!is_string($relation)) {
            throw new TypeError(self::class . '::parse(): Second item of argument #1 ($args[1]) must be of type string, ' . get_debug_type($relation) . ' given.');
        }
        return new self($linkedEntities, $relation);
    }
}
