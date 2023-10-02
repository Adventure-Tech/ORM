<?php

use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;

// GET

test('Can get public property from entity', function () {
    $entity = new #[Entity] class {
        public string $foo = 'value';
    };
    expect(EntityAccessorService::get($entity, 'foo'))->toBe('value');
});

test('Can get private property from entity via getter', function () {
    $entity = new #[Entity] class {
        private string $foo = 'value';

        public function getFoo(): string
        {
            return $this->foo;
        }
    };
    expect(EntityAccessorService::get($entity, 'foo'))->toBe('value');
});

test('Getters take priority over public access', function () {
    $entity = new #[Entity] class {
        public string $foo = 'value';

        public function getFoo(): string
        {
            return 'other value';
        }
    };
    expect(EntityAccessorService::get($entity, 'foo'))->toBe('other value');
});

test('Trying to get unset public property leads to null', function () {
    $entity = new #[Entity] class {
        public string $foo;
    };
    expect(EntityAccessorService::get($entity, 'foo'))->toBeNull();
});

// GET_ID

test('Can get public ID from entity', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column]  public string $foo = 'value';
    };
    expect(EntityAccessorService::getId($entity))->toBe('value');
});

test('Can get private ID from entity via getter', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column]  private string $foo = 'value';

        public function getFoo(): string
        {
            return $this->foo;
        }
    };
    expect(EntityAccessorService::getId($entity))->toBe('value');
});

test('Getters for ID take priority over public access', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column]  public string $foo = 'value';

        public function getFoo(): string
        {
            return 'other value';
        }
    };
    expect(EntityAccessorService::getId($entity))->toBe('other value');
});

test('Trying to get unset public ID leads to null', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column]  public string $foo;
    };
    expect(EntityAccessorService::getId($entity))->toBeNull();
});

// SET

test('Can set public property on entity', function () {
    $entity = new #[Entity] class {
        public string $foo = 'value';
    };
    EntityAccessorService::set($entity, 'foo', 'new value');
    expect($entity->foo)->toBe('new value');
});

test('Can set private property on entity via setter', function () {
    $entity = new #[Entity] class {
        private string $foo = 'value';

        public function setFoo(string $foo): void
        {
            $this->foo = $foo;
        }
    };
    EntityAccessorService::set($entity, 'foo', 'new value');
    expect(getProperty($entity, 'foo'))->toBe('new value');
});

test('Setters take priority over public access', function () {
    $entity = new #[Entity] class {
        public string $foo = 'value';

        public function setFoo(string $foo): void
        {
            $this->foo = 'other value';
        }
    };
    EntityAccessorService::set($entity, 'foo', 'new value');
    expect(getProperty($entity, 'foo'))->toBe('other value');
});

// SET_ID

test('Can set public ID on entity', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column] public string $foo = 'value';
    };
    EntityAccessorService::setId($entity, 'new value');
    expect($entity->foo)->toBe('new value');
});

test('Can set private ID on entity via setter', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column] private string $foo = 'value';

        public function setFoo(string $foo): void
        {
            $this->foo = $foo;
        }
    };
    EntityAccessorService::setId($entity, 'new value');
    expect(getProperty($entity, 'foo'))->toBe('new value');
});

test('Setters for ID take priority over public access', function () {
    $entity = new #[Entity] class {
        #[ID] #[Column] public string $foo = 'value';

        public function setFoo(string $foo): void
        {
            $this->foo = 'other value';
        }
    };
    EntityAccessorService::setId($entity, 'new value');
    expect(getProperty($entity, 'foo'))->toBe('other value');
});

// IS_SET

test('Can check if public property on entity is set', function (object $entity, bool $expected) {
    expect(EntityAccessorService::isset($entity, 'foo'))->toBe($expected);
})->with([
    'unset' => fn() => [new #[Entity] class {
        public string $foo;
    }, false],
    'null' => fn() => [new #[Entity] class {
        public ?string $foo = null;
    }, false],
    'set' => fn() => [new #[Entity] class {
        public ?string $foo = 'value';
    }, true],
]);

test('Can check if private property on entity is set via getter', function (?string $value, bool $expected) {
    $entity = new #[Entity] class ($value) {
        public function __construct(private ?string $foo)
        {
        }

        public function getFoo(): ?string
        {
            return $this->foo;
        }
    };
    expect(EntityAccessorService::isset($entity, 'foo'))->toBe($expected);
})->with([
    'null' => [null, false],
    'set' => ['value', true],
]);

test('Getters take priority over public access when checkin if property is set', function (?string $propertyValue, ?string $getterValue, bool $expected) {
    $entity = new #[Entity] class ($propertyValue, $getterValue) {
        public function __construct(private ?string $foo, private ?string $bar)
        {
        }

        public function getFoo(): ?string
        {
            return $this->bar;
        }
    };
    expect(EntityAccessorService::isset($entity, 'foo'))->toBe($expected);
})->with([
    'both null'         => [null, null, false],
    'non-null property' => ['xx', null, false],
    'non-null getter'   => [null, 'xx', true],
    'both non-null'     => ['xx', 'xx', true],
]);

// IS_SET_ID

test('Can check if public ID on entity is set', function (object $entity, bool $expected) {
    expect(EntityAccessorService::issetId($entity))->toBe($expected);
})->with([
    'unset' => fn() => [new #[Entity] class {
        #[ID] #[Column] public string $foo;
    }, false],
    'null' => fn() => [new #[Entity] class {
        #[ID] #[Column] public ?string $foo = null;
    }, false],
    'set' => fn() => [new #[Entity] class {
        #[ID] #[Column] public ?string $foo = 'value';
    }, true],
]);

test('Can check if private ID on entity is set via getter', function (?string $value, bool $expected) {
    $entity = new #[Entity] class ($value) {
        public function __construct(#[ID] #[Column] private ?string $foo)
        {
        }

        public function getFoo(): ?string
        {
            return $this->foo;
        }
    };
    expect(EntityAccessorService::issetId($entity))->toBe($expected);
})->with([
    'null' => [null, false],
    'set' => ['value', true],
]);

test('Getters take priority over public access when checkin if ID is set', function (?string $propertyValue, ?string $getterValue, bool $expected) {
    $entity = new #[Entity] class ($propertyValue, $getterValue) {
        public function __construct(#[ID] private ?string $foo, private ?string $bar)
        {
        }

        public function getFoo(): ?string
        {
            return $this->bar;
        }
    };
    expect(EntityAccessorService::isset($entity, 'foo'))->toBe($expected);
})->with([
    'both null'         => [null, null, false],
    'non-null property' => ['xx', null, false],
    'non-null getter'   => [null, 'xx', true],
    'both non-null'     => ['xx', 'xx', true],
]);
