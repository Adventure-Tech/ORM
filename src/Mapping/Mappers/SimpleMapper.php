<?php

namespace AdventureTech\ORM\Mapping\Mappers;

/**
 * @template-covariant  T
 * @extends Mapper<T>
 */
interface SimpleMapper extends Mapper
{
    public function __construct(string $name);
}
