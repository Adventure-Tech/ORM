<?php

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityInstantiationException;
use AdventureTech\ORM\Exceptions\EntityReflectionInstantiationException;
use AdventureTech\ORM\Exceptions\MultipleIdColumnsException;
use AdventureTech\ORM\Exceptions\NullReflectionTypeException;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\ManagedColumns\ManagedColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Mapping\SoftDeletes\SoftDeleteAnnotation;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;
use AdventureTech\ORM\Tests\TestClasses\PostRepository;
use Illuminate\Support\Collection;
use Mockery\MockInterface;

test('Entity reflection supports faking', function () {
    $mock = EntityReflection::fake();
    expect(EntityReflection::new('anything'))
        ->toBeInstanceOf(MockInterface::class)
        ->toBe($mock);
    EntityReflection::resetFake();
    expect(EntityReflection::new(User::class))
        ->toBeInstanceOf(EntityReflection::class)
        ->not->toBeInstanceOf(MockInterface::class);
});

test('invalid class name throws exception', function () {
    expect(fn() => EntityReflection::new('invalid'))
        ->toThrow(
            EntityReflectionInstantiationException::class,
            'EntityReflection class can only be instantiated for a valid entity [attempted instantiation for "invalid"]'
        );
});

test('class without entity annotation throws exception', function () {
    expect(fn() => EntityReflection::new(stdClass::class))
        ->toThrow(
            EntityReflectionInstantiationException::class,
            'EntityReflection class can only be instantiated for a valid entity [attempted instantiation for "stdClass"]'
        );
});

test('Can instantiate entity via reflection', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->newInstance())->toBeInstanceOf(User::class);
});

test('Can get table name correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getTableName())->toBe('users');
});

test('Can get id column name correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getId())->toBe('id');
});

test('Can get list of mappers correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getMappers())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(6)
        ->each->toBeInstanceOf(Mapper::class);
});

test('Can get list of linkers correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getLinkers())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(Linker::class);
});

test('Can get class name correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getClass())->toBe(User::class);
});

test('Can names of selected columns correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getSelectColumns())
        ->toBeArray()
        ->toEqualCanonicalizing(['id', 'name', 'created_at', 'updated_at', 'deleted_at', 'favourite_color'])
        ->each(fn ($expectation, $key) => $expectation->toBe($key));
});

test('Can names of selected columns correctly with owning linker (BelongsTo)', function () {
    $entityReflection = EntityReflection::new(Post::class);
    expect($entityReflection->getSelectColumns())
        ->toBeArray()
        ->toEqualCanonicalizing([
            'id',
            'title',
            'content',
            'published_at',
            'published_tz',
            'created_at',
            'updated_at',
            'deleted_at',
            'author',
            'editor',
        ])
        ->each(fn ($expectation, $key) => $expectation->toBe($key));
});

test('Can get repository name correctly if set on entity', function () {
    $entityReflection = EntityReflection::new(Post::class);
    expect($entityReflection->getRepository())->toBe(PostRepository::class);
});

test('Repository name is null if not set on entity', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getRepository())->toBeNull();
});

test('Can get factory name correctly if set on entity', function () {
    $entityReflection = EntityReflection::new(Post::class);
    expect($entityReflection->getFactory())->toBe(PostFactory::class);
});

test('Factory name is null if not set on entity', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getFactory())->toBeNull();
});

test('Can get list of managed columns correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getManagedColumns())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(ManagedColumnAnnotation::class);
});

test('Can get list of soft-delete columns correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getSoftDeletes())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->each->toBeInstanceOf(SoftDeleteAnnotation::class);
});

test('Entity with multiple id columns leads to exception', function () {
    $class = new #[Entity] class {
        #[Id]
        public string $a;
        #[Id]
        public string $b;
    };
    expect(fn() => EntityReflection::new($class::class))
        ->toThrow(MultipleIdColumnsException::class, 'Cannot have multiple ID columns');
});

test('Properties annotated as relations must have type set', function () {
    $class = new #[Entity] class {
        #[BelongsTo]
        public $a;
    };
    expect(fn() => EntityReflection::new($class::class))
        ->toThrow(NullReflectionTypeException::class, 'Reflection type returned null');
});


test('If entity instantiation fails an appropriate exception is thrown', function () {
    $class = new #[Entity] class ('arg')
    {
        public function __construct($arg)
        {
        }
    };
    $entityReflection = EntityReflection::new($class::class);
    expect(fn () =>$entityReflection->newInstance())
    ->toThrow(EntityInstantiationException::class);
});

test('Entity reflection can check if property is set on an instance', function () {
    $class = new #[Entity] class ('arg')
    {
        public string $a;
        public string $b = 'set';
    };
    $entityReflection = EntityReflection::new($class::class);
    expect($entityReflection->checkPropertyInitialized('a', $class))->toBeFalse()
        ->and($entityReflection->checkPropertyInitialized('b', $class))->toBeTrue();
});
