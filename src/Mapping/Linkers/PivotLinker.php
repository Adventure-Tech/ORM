<?php

namespace AdventureTech\ORM\Mapping\Linkers;

/**
 * @template T of object
 */
interface PivotLinker
{
    public function getPivotTable(): string;
    public function getOriginForeignKey(): string;
    public function getTargetForeignKey(): string;

    /**
     * @return class-string<T>
     */
    public function getTargetEntity(): string;
}
