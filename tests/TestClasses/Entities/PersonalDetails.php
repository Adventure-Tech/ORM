<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumn;
use AdventureTech\ORM\Mapping\Columns\StringColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedDatetimes\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;

#[Entity]
class PersonalDetails
{
    use WithTimestamps;

    #[Id]
    #[IntColumn]
    public int $id;

    #[StringColumn]
    public ?string $phone;

    #[StringColumn]
    public string $email;

    #[StringColumn]
    public ?string $address;

    #[StringColumn]
    public ?string $zip;

    #[StringColumn]
    public string $country = 'NOR';

    #[BelongsTo]
    public User $user;
}
