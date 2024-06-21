<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;

/**
 * @extends PersistenceManager<Post>
 */
class PostPersistence extends PersistenceManager
{
    protected static function getEntityClassName(): string
    {
        return Post::class;
    }
}
