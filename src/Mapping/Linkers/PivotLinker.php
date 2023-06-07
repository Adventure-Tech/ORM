<?php

namespace AdventureTech\ORM\Mapping\Linkers;

interface PivotLinker
{
    public function getPivotTable(): string;
    public function getOriginForeignKey(): string;
    public function getTargetForeignKey(): string;
}
