<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\EntityReflection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use stdClass;

/**
 * @template T of object
 */
class Repository
{
    /**
     * @param  EntityReflection<T>  $entityReflection
     */
    private function __construct(private readonly EntityReflection $entityReflection)
    {
    }

    /**
     * @param  class-string<T>  $class
     *
     * @return Repository<T>
     */
    public static function new(string $class): Repository
    {
        $entityReflection = new EntityReflection($class);
        $repository = $entityReflection->getRepository();
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
     * @return void
     */
    public function dd(): void
    {
        $this->buildQuery()->dd();
    }

//    private array $where = [];
//
//    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
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

        $softDeleteColumn = $this->entityReflection->getSoftDeleteColumn();
        if (!is_null($softDeleteColumn)) {
            $query->whereNull($softDeleteColumn);
        }

        foreach ($this->with as $index => $tmp) {
            self::applyJoin($query, $tmp, $this->entityReflection->getTableName(), self::createAlias($index));
        }
        return $query;
    }

    private static function applyJoin(Builder $query, TMP $tmp, string $from, string $to): void
    {
        $tmp->relationInstance->join($query, $from, $to);
        foreach ($tmp->repository->with as $index => $subTmp) {
            self::applyJoin($query, $subTmp, $to, self::createAlias($index, $to));
        }
    }

    /**
     * @var array<int,TMP>
     */
    private array $with = [];

    /**
     * @param  string         $relation
     * @param  callable|null  $callable
     *
     * @return $this<T>
     */
    public function with(string $relation, callable $callable = null): static
    {
        if (!$this->entityReflection->getRelations()->has($relation)) {
            dump($this->entityReflection->getClass(), $relation);
            throw new LogicException('Invalid relation used in with clause');
        }

        $relationInstance = $this->entityReflection->getRelations()->get($relation);

        // if a MorphTo relationship get multiple target entities
        $repository = self::new($relationInstance->getTargetEntity());

        // apply callback to target repository
        if ($callable) {
            $callable($repository);
        }

        $this->with[] = new TMP($relationInstance, $repository);

        return $this;
    }

    private ?int $resolvingId = null;
    private mixed $resolvingEntity;

    private function resolve(stdClass $item, string $alias = '', bool $reset = false)
    {
        $id = $item->{$alias . $this->entityReflection->getId()};
        if ($id !== $this->resolvingId) {
            $this->resolvingId = $id;
            $this->resolvingEntity = $this->entityReflection->newInstance();
            foreach ($this->entityReflection->getColumns() as $property => $column) {
                $this->resolvingEntity->{$property} = $column->deserialize($item, $alias);
            }
            $reset = true;
        }

        foreach ($this->with as $index => $tmp) {
            $newAlias = self::createAlias($index, $alias);
            $entity = $tmp->repository->resolve($item, $newAlias, $reset);
            $tmp->relationInstance->link($this->resolvingEntity, $entity);
        }

        return $reset && isset($this->resolvingEntity) ? $this->resolvingEntity : null;
    }

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
