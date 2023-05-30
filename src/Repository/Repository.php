<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;
use stdClass;

/**
 * @template T of object
 */
class Repository
{
    /**
     * @var array<int,TMP<T,object>>
     */
    private array $with = [];

    private ?int $resolvingId = null;
    /**
     * @var T
     */
    private object $resolvingEntity;

    /**
     * @param  EntityReflection<T>  $entityReflection
     */
    private function __construct(private readonly EntityReflection $entityReflection)
    {
    }

    /**
     * @template E of object
     * @param  class-string<E>  $class
     *
     * @return Repository<E>
     */
    public static function new(string $class): Repository
    {
        $entityReflection = EntityReflection::new($class);
        $repository = $entityReflection->getRepository() ?? self::class;
        return new $repository($entityReflection);
    }

    /**
     * @return Collection<int,T>
     */
    public function get(): Collection
    {
        return $this->mapToEntities($this->buildQuery()->get());
    }

    /**
     * @param  int  $id
     *
     * @return T|null
     */
    public function find(int $id)
    {
        return $this->mapToEntities(
            $this->buildQuery()
            ->where($this->entityReflection->getTableName() . '.' . $this->entityReflection->getId(), $id)
            ->limit(1)
            ->get()
        )->first();
    }

    /**
     * @param  int  $id
     *
     * @return T
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $id)
    {
        $entity = $this->find($id);
        if (is_null($entity)) {
            throw new EntityNotFoundException($this->entityReflection->getClass(), $id);
        }
        return $entity;
    }

    /**
     * @return void
     */
    #[NoReturn] public function dd(): void
    {
        $this->buildQuery()->dd();
    }

    /**
     * @param  string         $relation
     * @param  callable|null  $callable
     *
     * @return $this<T>
     */
    public function with(string $relation, callable $callable = null): static
    {
        if (!$this->entityReflection->getLinkers()->has($relation)) {
            throw new InvalidRelationException('Invalid relation used in with clause');
        }

        /** @var Linker<T,object> $linker */
        $linker = $this->entityReflection->getLinkers()->get($relation);

        $repository = self::new($linker->getTargetEntity());

        if ($callable) {
            $callable($repository);
        }

        $this->with[] = new TMP($linker, $repository);

        return $this;
    }

//    private array $filter = [];
//
//    public function filter($column, $operator = null, $value = null, $boolean = 'and'): static
//    {
//        $this->where[] = fn (Builder $query) => $query->where($column, $operator, $value, $boolean);
//        return $this;
//    }

    private static function createAlias(int $index, string $previousAlias = ''): string
    {
        return '_' . $index . $previousAlias;
    }

    private function buildQuery(): Builder
    {
        $query = DB::table($this->entityReflection->getTableName())
            ->select($this->entityReflection->getSelectColumns());

        // TODO: add support for soft-deletes
        // TODO: add support for circumventing soft-deletes
//        foreach ($this->entityReflection->getManagedDatetimes() as $managedDatetime) {
//            if ($managedDatetime instanceof ManagedDeletedAt) {
//                $query->whereNull($this->entityReflection->getTableName() . '.' . $managedDatetime->getColumnName());
//            }
//        }

        foreach ($this->with as $index => $tmp) {
            self::applyJoin($query, $tmp, $this->entityReflection->getTableName(), self::createAlias($index));
        }
        return $query;
    }

    /**
     * @template S of object
     * @param  Builder  $query
     * @param  TMP<S,object>  $tmp
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    private static function applyJoin(Builder $query, TMP $tmp, string $from, string $to): void
    {
        $tmp->linker->join($query, $from, $to);
        foreach ($tmp->repository->with as $index => $subTmp) {
            self::applyJoin($query, $subTmp, $to, self::createAlias($index, $to));
        }
    }




    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @param  bool  $reset
     * @return T|null
     */
    private function resolve(stdClass $item, string $alias = '', bool $reset = false): ?object
    {
        $id = $item->{$alias . $this->entityReflection->getId()};
        if ($id !== $this->resolvingId) {
            $this->resolvingId = $id;
            $this->resolvingEntity = $this->entityReflection->newInstance();
            foreach ($this->entityReflection->getMappers() as $property => $mapper) {
                $this->resolvingEntity->{$property} = $mapper->deserialize($item, $alias);
            }
            foreach ($this->entityReflection->getManagedDatetimes() as $property => $managedDatetime) {
                $this->resolvingEntity->{$property} = $managedDatetime->deserialize($item, $alias);
            }
            $reset = true;
        }

        foreach ($this->with as $index => $tmp) {
            $newAlias = self::createAlias($index, $alias);
            $entity = $tmp->repository->resolve($item, $newAlias, $reset);
            $tmp->linker->link($this->resolvingEntity, $entity);
        }

        return $reset && isset($this->resolvingEntity) ? $this->resolvingEntity : null;
    }

    /**
     * @param  Collection<int|string,stdClass>  $data
     * @return Collection<int,T>
     */
    private function mapToEntities(Collection $data): Collection
    {
        $result = Collection::empty();
        foreach ($data as $item) {
            if ($entity = $this->resolve($item)) {
                $result[] = $entity;
            }
        }
        return $result;
    }
}
