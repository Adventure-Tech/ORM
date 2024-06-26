<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Tests\TestClasses\BackedEnum;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Factory::resetFakers(Post::class);
});

test('Can create single entity', function () {
    $user = Factory::new(User::class)->create();
    expect(DB::table('users')->count())->toBe(1)
        ->and($user)->toBeInstanceOf(User::class);
});

test('Can make single entity', function () {
    $user = Factory::new(User::class)->make();
    expect(DB::table('users')->count())->toBe(0)
    ->and($user)->toBeInstanceOf(User::class);
});

test('Non-nullable owning relations are automatically created correctly', function () {
    Factory::new(Post::class)->create();
    expect(DB::table('posts')->count())->toBe(1)
        ->and(DB::table('users')->count())->toBe(1);
});

test('Can set owning relations as instances', function () {
    $editor = Factory::new(User::class)->create();
    Factory::new(Post::class)->create([
        'editor' => $editor
    ]);
    expect(DB::table('posts')->first()->editor)->toBe($editor->getIdentifier());
});

test('Can create entity with enum', function () {
    $number = Factory::new(Post::class)->create();

    expect($number->number)->toBeIn(BackedEnum::cases());
});

test('Can set owning relations as factories', function () {
    $userFactory = Factory::new(User::class);
    Factory::new(Post::class)->create([
        'editor' => $userFactory
    ]);
    $post = DB::table('posts')->first();
    expect(DB::table('posts')->count())->toBe(1)
        ->and(DB::table('users')->count())->toBe(2)
        ->and($post->author)->not->toBe($post->editor);
});

test('Can set state in state method', function () {
    $factory = Factory::new(User::class)->state(['name' => 'Alice']);
    $userA = $factory->create();
    $userB = $factory->create();
    expect($userA->name)->toBe('Alice')
        ->and($userB->name)->toBe('Alice')
        ->and(DB::table('users')->count())->toBe(2)
        ->and(DB::table('users')->get()->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Alice']);
});

test('Can override state in create method', function () {
    $factory = Factory::new(User::class)->state(['name' => 'Alice']);
    $userA = $factory->create();
    $userB = $factory->create(['name' => 'Bob']);
    $userC = $factory->create();
    expect($userA->name)->toBe('Alice')
        ->and($userB->name)->toBe('Bob')
        ->and($userC->name)->toBe('Alice')
        ->and(DB::table('users')->count())->toBe(3)
        ->and(DB::table('users')->get()->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Bob', 'Alice']);
});

test('Can create multiple', function () {
    $users = Factory::new(User::class)->createMultiple(5);
    expect(DB::table('users')->count())->toBe(5)
        ->and($users)->toHaveCount(5);
});

test('Can reuse factories', function () {
    $author = Factory::new(User::class)->create(['name' => 'Alice']);
    $editorFactory = Factory::new(User::class)->state(['name' => 'Bob']);
    $posts = Factory::new(Post::class)->state([
        'author' => $author,
        'editor' => $editorFactory,
    ])->createMultiple(3);

    expect(DB::table('posts')->count())->toBe(3)
        ->and(DB::table('users')->count())->toBe(4)
        ->and(DB::table('users')->where('name', 'Alice')->count())->toBe(1)
        ->and(DB::table('users')->where('name', 'Bob')->count())->toBe(3)
        ->and($posts->map(fn(Post $post) => $post->author->getIdentifier())->toArray())->toEqualCanonicalizing([
            $author->getIdentifier(),
            $author->getIdentifier(),
            $author->getIdentifier(),
        ]);
});

test('Can generate unique values via faker in factories', function () {
    $underLimit = fn() => Factory::new(Post::class)->createMultiple(91);
    $overLimit = fn() => Factory::new(Post::class)->createMultiple(1);
    expect($underLimit)->not->toThrow(OverflowException::class)
        ->and($underLimit)->not->toThrow(OverflowException::class)
        ->and($overLimit)->toThrow(
            OverflowException::class,
            'Maximum retries of 10000 reached without finding a unique value'
        );
});

test('Factories uses default values if set in the entity', function () {
    $fooEntityClass = new  #[Entity] class ()
    {
        #[Id]
        #[Column]
        public int $id;
        #[Column]
        public string $string;
        #[Column]
        public int $int;
        #[Column]
        public array $array;
        #[Column]
        public float $float;
        #[Column]
        public bool $bool;
        #[Column]
        public CarbonImmutable $carbonImmutable;
    };
    $entity = Factory::new($fooEntityClass::class)->make();
    expect($entity)
        ->int->toBeInt()
        ->string->toBeString()
        ->bool->toBeBool()
        ->float->toBeFloat()
        ->array->toBeArray()
        ->carbonImmutable->toBeInstanceOf(CarbonImmutable::class);
});

test('Factories throws exception if no default provided for a given type', function () {
    $fooEntityClass = new  #[Entity] class ()
    {
        #[Id]
        #[Column]
        public int $id;
        #[Column]
        public Carbon $bar;
    };
    expect(fn() => Factory::new($fooEntityClass::class)->make())->toThrow(
        \AdventureTech\ORM\Exceptions\FactoryException::class,
        'No default for type "Carbon\Carbon" of property "bar". Make sure to register a value in a custom factory class.'
    );
});
