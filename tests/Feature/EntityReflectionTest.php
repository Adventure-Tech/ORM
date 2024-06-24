<?php

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityReflectionException;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\Linkers\OwningLinker;
use AdventureTech\ORM\Mapping\ManagedColumns\ManagedColumnAnnotation;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Mapping\SoftDeletes\SoftDeleteAnnotation;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;
use AdventureTech\ORM\Tests\TestClasses\Repositories\PostRepository;
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
            EntityReflectionException::class,
            'Failed to reflect "invalid".'
        );
});

test('class without entity annotation throws exception', function () {
    expect(fn() => EntityReflection::new(stdClass::class))
        ->toThrow(
            EntityReflectionException::class,
            'Missing #[Entity] attribute annotation on "stdClass".'
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
    expect($entityReflection->getIdColumn())->toBe('id');
});

test('Can get id property name correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getIdProperty())->toBe('identifier');
});

test('Can get list of mappers correctly', function () {
    $entityReflection = EntityReflection::new(User::class);
    expect($entityReflection->getMappers())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(6)
        ->each->toBeInstanceOf(Mapper::class);
});

test('Can get list of owning linkers correctly', function () {
    $entityReflection = EntityReflection::new(Post::class);
    expect($entityReflection->getOwningLinkers())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Linker::class)->toBeInstanceOf(OwningLinker::class);
});
// TODO: getLinker method tests

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
            'number',
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
        ->toThrow(
            EntityReflectionException::class,
            'Multiple ID columns defined on "' . $class::class . '" which is not supported.'
        );
});

test('Properties annotated as relations must have type set', function () {
    $class = new #[Entity] class {
        #[BelongsTo]
        public $a;
    };
    expect(fn() => EntityReflection::new($class::class))
        ->toThrow(EntityReflectionException::class, 'Type hints are mandatory and must not be union or intersection types');
});


test('If entity instantiation fails an appropriate exception is thrown', function () {
    $class = new #[Entity] class ('arg')
    {
        #[ID] #[Column] public int $id;
        public function __construct($arg)
        {
        }
    };
    $entityReflection = EntityReflection::new($class::class);
    expect(fn () => $entityReflection->newInstance())->toThrow(EntityReflectionException::class);
});

test('Entity reflection can check if property is nullable', function () {
    $class = new #[Entity] class {
        #[ID] #[Column] public int $id;
        public string $a;
        public ?string $b;
    };
    $entityReflection = EntityReflection::new($class::class);
    expect($entityReflection->allowsNull('a'))->toBeFalse()
        ->and($entityReflection->allowsNull('b'))->toBeTrue();
});

test('Entity reflection null-check throws exception if property does not exist', function () {
    $class = new #[Entity] class {
        #[ID] #[Column] public int $id;
    };
    $entityReflection = EntityReflection::new($class::class);
    expect(fn () => $entityReflection->allowsNull('a'))->toThrow(ReflectionException::class);
});

test('Entity reflection null-check throws exception for property without type', function () {
    $class = new #[Entity] class {
        #[ID] #[Column] public int $id;
        public $a;
    };
    $entityReflection = EntityReflection::new($class::class);
    expect(fn () => $entityReflection->allowsNull('a'))->toThrow(
        EntityReflectionException::class,
        'Type hints are mandatory and must not be union or intersection types'
    );
});

test('Entity reflection requires ID annotation', function () {
    $class = new #[Entity] class {
    };
    expect(fn() => EntityReflection::new($class::class))->toThrow(
        EntityReflectionException::class,
        'ID column missing on "' . $class::class . '". Annotate a property with the #[Id] attribute.'
    );
});

test('Entity reflection requires the ID column to be mapped', function () {
    $class = new #[Entity] class {
        #[ID] public int $id;
    };
    expect(fn () => EntityReflection::new($class::class))->toThrow(
        EntityReflectionException::class,
        'Missing mapper annotation for the ID column "id" on "' . $class::class . '"'
    );
});
