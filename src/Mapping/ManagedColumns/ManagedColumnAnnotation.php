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
     * @return T|null
     */
    public function getUpdateValue(): mixed;
    /**
     * @return T|null
     */
    public function getDeleteValue(): mixed;
}
