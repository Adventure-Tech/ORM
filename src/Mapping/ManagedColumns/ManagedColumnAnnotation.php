<?php

namespace AdventureTech\ORM\Mapping\ManagedColumns;

/**
 * @template T
 */
interface ManagedColumnAnnotation
{
    /**
     * @return T|null
     */
    public function getInsertValue(): mixed;

    /**
     * @param  mixed  $value
     * @return T|null
     */
    public function getUpdateValue(mixed $value): mixed;
    /**
     * @return T|null
     */
    public function getDeleteValue(): mixed;
}
