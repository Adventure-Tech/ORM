<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\Persistors\DeletePersistor;
use Carbon\CarbonImmutable;

class CustomDelete extends DeletePersistor
{
    public function __construct(string $entityClassName, CarbonImmutable $deletedAt)
    {
        parent::__construct($entityClassName);
        $this->softDeleteDatetimes->transform(fn(CarbonImmutable $value) => $deletedAt);
    }
}
