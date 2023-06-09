<?php

use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Repositories\PostRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Can load relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    $postId = DB::table('posts')->insertGetId(['title' => 'Title', 'content' => 'Content', 'author' => $authorId]);
    $user = Repository::new(User::class)
        ->with('posts')
        ->find($authorId);
    expect($user->posts)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->first()->id->toBe($postId);
});

test('Not loaded relations are not set in the entity', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insertGetId(['title' => 'Title', 'content' => 'Content', 'author' => $authorId]);
    $user = Repository::new(User::class)->find($authorId);
    expect(fn() =>$user->posts)->toThrow(Error::class);
});

test('Can load relations within relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insertGetId(['title' => 'Title', 'content' => 'Content', 'author' => $authorId]);
    $user = Repository::new(User::class)
        ->with('posts', function (PostRepository $repository) {
            $repository->with('author');
        })
        ->find($authorId);
    expect($user->posts->first()->author)
        ->toBeInstanceOf(User::class)
        ->id->toBe($authorId);
});

test('Trying to load invalid relation leads to exception', function () {
    expect(fn() => Repository::new(User::class)->with('invalid'))
        ->toThrow(
            InvalidRelationException::class,
            'Invalid relation used in with clause [tried to load relation "invalid"]'
        );
});
