<?php

namespace AdventureTech\ORM\Mapping\SoftDeletes;

use Carbon\CarbonImmutable;

interface SoftDeleteAnnotation
{
    public function getDatetime(): CarbonImmutable;
}
