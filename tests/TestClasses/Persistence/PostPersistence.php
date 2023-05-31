<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;

class PostPersistence extends PersistenceManager
{
    public function __construct()
    {
        parent::__construct(Post::class);
    }
}
