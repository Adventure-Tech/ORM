<?php

namespace AdventureTech\ORM\Tests\TestClasses;

use AdventureTech\ORM\Repository\Repository;

class PostRepository extends Repository
{
    public function customFilterMethod(): self
    {
//        $this->filter(new Where(...));
        dump('asd');
        return $this;
    }
}
