<?php

use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Facades\DB;

test('Can sort in ascending order', function () {
    $data = [
        ['name' => 'A'],
        ['name' => 'B'],
        ['name' => 'C'],
    ];
    shuffle($data);
    DB::table('users')->insert($data);
    $users = Repository::new(User::class)->orderBy('name')->get();
    expect($users->pluck('name')->toArray())->toEqual(['A','B','C']);
});

test('Can sort in descending order', function () {
    $data = [
        ['name' => 'B'],
        ['name' => 'C'],
        ['name' => 'A'],
    ];
    shuffle($data);
    DB::table('users')->insert($data);
    $users = Repository::new(User::class)->orderByDesc('name')->get();
    expect($users->pluck('name')->toArray())->toEqual(['C','B','A']);
});

test('Can sort in ascending order within loaded relation', function () {
    $userData = [
        ['id' => 1, 'name' => 'A'],
        ['id' => 2, 'name' => 'B'],
        ['id' => 3, 'name' => 'C'],
    ];
    shuffle($userData);
    DB::table('users')->insert($userData);
    $postData = [
        ['title' => 'A1', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'A2', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'A3', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'B1', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'B2', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'B3', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'C1', 'content' => 'content', 'number' => 1, 'author' => 3],
        ['title' => 'C2', 'content' => 'content', 'number' => 1, 'author' => 3],
        ['title' => 'C3', 'content' => 'content', 'number' => 1, 'author' => 3],
    ];
    shuffle($postData);
    DB::table('posts')->insert($postData);
    $users = Repository::new(User::class)
        ->orderBy('name')
        ->with('posts', fn(Repository $repository) => $repository->orderBy('title'))
        ->get();
    expect($users->pluck('name')->toArray())->toEqual(['A', 'B', 'C'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['C1', 'C2', 'C3'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['B1', 'B2', 'B3'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['A1', 'A2', 'A3']);
});

test('Can sort in descending order within loaded relation', function () {
    $userData = [
        ['id' => 1, 'name' => 'A'],
        ['id' => 2, 'name' => 'B'],
        ['id' => 3, 'name' => 'C'],
    ];
    shuffle($userData);
    DB::table('users')->insert($userData);
    $postData = [
        ['title' => 'A1', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'A2', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'A3', 'content' => 'content', 'number' => 1, 'author' => 1],
        ['title' => 'B1', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'B2', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'B3', 'content' => 'content', 'number' => 1, 'author' => 2],
        ['title' => 'C1', 'content' => 'content', 'number' => 1, 'author' => 3],
        ['title' => 'C2', 'content' => 'content', 'number' => 1, 'author' => 3],
        ['title' => 'C3', 'content' => 'content', 'number' => 1, 'author' => 3],
    ];
    shuffle($postData);
    DB::table('posts')->insert($postData);
    $users = Repository::new(User::class)
        ->orderByDesc('name')
        ->with('posts', fn(Repository $repository) => $repository->orderByDesc('title'))
        ->get();
    expect($users->pluck('name')->toArray())->toEqual(['C', 'B', 'A'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['A3', 'A2', 'A1'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['B3', 'B2', 'B1'])
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual(['C3', 'C2', 'C1']);
});
