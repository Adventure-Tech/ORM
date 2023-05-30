<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumnAnnotation;
use AdventureTech\ORM\Mapping\Columns\StringColumnAnnotation;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedDatetimes\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;

#[Entity]
class PersonalDetails
{
    use WithTimestamps;

    #[Id]
    #[IntColumnAnnotation]
    public int $id;

    #[StringColumnAnnotation]
    public ?string $phone;

    #[StringColumnAnnotation]
    public string $email;

    #[StringColumnAnnotation]
    public ?string $address;

    #[StringColumnAnnotation]
    public ?string $zip;

    #[StringColumnAnnotation]
    public string $country = 'NOR';

    #[BelongsTo]
    public User $user;
}
