<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Direction;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Facades\DB;

test('can sort', function (Direction $direction, array $expected) {
    $data = [
        ['name' => 'A'],
        ['name' => 'B'],
        ['name' => 'C'],
    ];
    shuffle($data);
    DB::table('users')->insert($data);
    $users = Repository::new(User::class)->orderBy('name', $direction)->get();
    expect($users->pluck('name')->toArray())->toEqual($expected);
})->with([
    'ascending' => [Direction::ASCENDING, ['A','B','C']],
    'descending' => [Direction::DESCENDING, ['C','B','A']],
]);

test('can sort using directed methods', function (string $method, array $expected) {
    $data = [
        ['name' => 'B'],
        ['name' => 'C'],
        ['name' => 'A'],
    ];
    shuffle($data);
    DB::table('users')->insert($data);
    $users = Repository::new(User::class)->$method('name')->get();
    expect($users->pluck('name')->toArray())->toEqual($expected);
})-> with([
    'ascending' => ['orderByAsc', ['A','B','C']],
    'descending' => ['orderByDesc', ['C','B','A']],
]);

test('can sort within loaded relation', function (
    Direction $outer,
    Direction $inner,
    array $a,
    array $b,
    array $c,
    array $d
) {
    $userData = [
        ['id' => 1, 'name' => 'A'],
        ['id' => 2, 'name' => 'B'],
        ['id' => 3, 'name' => 'C'],
    ];
    shuffle($userData);
    DB::table('users')->insert($userData);
    $postData = [
        ['title' => 'a1', 'content' => 'content', 'number' => 'ONE', 'author' => 1],
        ['title' => 'a2', 'content' => 'content', 'number' => 'ONE', 'author' => 1],
        ['title' => 'a3', 'content' => 'content', 'number' => 'ONE', 'author' => 1],
        ['title' => 'b1', 'content' => 'content', 'number' => 'ONE', 'author' => 2],
        ['title' => 'b2', 'content' => 'content', 'number' => 'ONE', 'author' => 2],
        ['title' => 'b3', 'content' => 'content', 'number' => 'ONE', 'author' => 2],
        ['title' => 'c1', 'content' => 'content', 'number' => 'ONE', 'author' => 3],
        ['title' => 'c2', 'content' => 'content', 'number' => 'ONE', 'author' => 3],
        ['title' => 'c3', 'content' => 'content', 'number' => 'ONE', 'author' => 3],
    ];
    shuffle($postData);
    DB::table('posts')->insert($postData);
    $users = Repository::new(User::class)
        ->orderBy('name', $outer)
        ->with('posts', fn(Repository $repository) => $repository->orderBy('title', $inner))
        ->get();
    expect($users->pluck('name')->toArray())->toEqual($a)
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual($b)
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual($c)
        ->and($users->pop()->posts->pluck('title')->toArray())->toEqual($d);
})->with([
    [Direction::ASCENDING, Direction::ASCENDING, ['A', 'B', 'C'], ['c1', 'c2', 'c3'], ['b1', 'b2', 'b3'], ['a1', 'a2', 'a3']],
    [Direction::ASCENDING, Direction::DESCENDING, ['A', 'B', 'C'], ['c3', 'c2', 'c1'], ['b3', 'b2', 'b1'], ['a3', 'a2', 'a1']],
    [Direction::DESCENDING, Direction::ASCENDING, ['C', 'B', 'A'], ['a1', 'a2', 'a3'], ['b1', 'b2', 'b3'], ['c1', 'c2', 'c3']],
    [Direction::DESCENDING, Direction::DESCENDING, ['C', 'B', 'A'], ['a3', 'a2', 'a1'], ['b3', 'b2', 'b1'], ['c3', 'c2', 'c1']],
]);


test('sorting works with limit clause', function (array $orderBys, int $limit, array $expected) {
    Factory::new(Post::class)->create(['title' => 'A', 'content' => 'c']);
    Factory::new(Post::class)->create(['title' => 'C', 'content' => 'b']);
    Factory::new(Post::class)->create(['title' => 'B', 'content' => 'a']);
    Factory::new(Post::class)->create(['title' => 'B', 'content' => 'a']);

    $repository = Repository::new(Post::class)->limit($limit);
    foreach ($orderBys as $column => $direction) {
        $repository->orderBy($column, $direction);
    }

    $posts = $repository->get();

    expect($posts)
        ->toHaveCount($limit)
        ->pluck('id')->toArray()->toEqual($expected);
})->with([
    [['title' => Direction::ASCENDING], 2, [1,3]],
    [['title' => Direction::DESCENDING], 2, [2,3]],
    [['content' => Direction::ASCENDING], 2, [3,4]],
    [['content' => Direction::DESCENDING], 2, [1,2]],
    [['id' => Direction::DESCENDING, 'title' => Direction::ASCENDING], 2, [4,3]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 2, [1,4]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 4, [1,4,3,2]],
    [['id' => Direction::DESCENDING, 'title' => Direction::ASCENDING], 4, [4,3,2,1]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 0, []],
]);

test('sorting works with offset clause', function (array $orderBys, int $offset, array $expected) {
    Factory::new(Post::class)->create(['title' => 'A', 'content' => 'c']);
    Factory::new(Post::class)->create(['title' => 'C', 'content' => 'b']);
    Factory::new(Post::class)->create(['title' => 'B', 'content' => 'a']);
    Factory::new(Post::class)->create(['title' => 'B', 'content' => 'a']);

    $repository = Repository::new(Post::class)->offset($offset);
    foreach ($orderBys as $column => $direction) {
        $repository->orderBy($column, $direction);
    }

    $posts = $repository->get();

    expect($posts)
        ->toHaveCount(4 - $offset)
        ->pluck('id')->toArray()->toEqual($expected);
})->with([
    [['title' => Direction::ASCENDING], 2, [4,2]],
    [['title' => Direction::DESCENDING], 2, [4,1]],
    [['content' => Direction::ASCENDING], 2, [2,1]],
    [['content' => Direction::DESCENDING], 2, [3,4]],
    [['id' => Direction::DESCENDING, 'title' => Direction::ASCENDING], 2, [2,1]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 2, [3,2]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 0, [1,4,3,2]],
    [['id' => Direction::DESCENDING, 'title' => Direction::ASCENDING], 0, [4,3,2,1]],
    [['title' => Direction::ASCENDING, 'id' => Direction::DESCENDING], 4, []],
]);
