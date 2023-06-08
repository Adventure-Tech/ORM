<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedColumns\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;

#[Entity]
class PersonalDetails
{
    use WithTimestamps;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public ?string $phone = null;

    #[Column]
    public string $email;

    #[Column]
    public ?string $address = null;

    #[Column]
    public ?string $zip = null;

    #[Column]
    public string $country = 'NOR';

    #[BelongsTo]
    public ?User $user = null;
}
