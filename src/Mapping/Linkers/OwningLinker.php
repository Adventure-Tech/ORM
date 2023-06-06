<?php

namespace AdventureTech\ORM\Mapping\Linkers;

interface OwningLinker
{
    public function getForeignKey(): string;
}
