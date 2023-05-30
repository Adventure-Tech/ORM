<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use Carbon\CarbonImmutable;

interface ManagedDatetimeAnnotation
{
    public function getInsertDatetime(): ?CarbonImmutable;
    public function getUpdateDatetime(?CarbonImmutable $datetime): ?CarbonImmutable;
    public function getDeleteDatetime(): ?CarbonImmutable;
}
