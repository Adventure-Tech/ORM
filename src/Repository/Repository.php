<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Repository\Filters\Filter;
use AdventureTech\ORM\Repository\Filters\IsNull;
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
     * @var Collection<string,LinkedRepository<T,object>>
     */
    private Collection $with;

    private ?int $resolvingId = null;
    /**
     * @var T
     */
    private object $resolvingEntity;
    private bool $includeDeleted = false;

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
     * @param  EntityReflection<T>  $entityReflection
     */
    private function __construct(private readonly EntityReflection $entityReflection)
    {
        $this->with = Collection::empty();
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
     * @return static
     */
    public function dump(): static
    {
        $this->buildQuery()->dump();
        return $this;
    }

    private int $aliasCounter = 0;
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

        // TODO: fix aliasing
        $this->with->put(
            $relation,
            new LinkedRepository($linker, $repository, self::createAlias($this->aliasCounter++))
        );

        return $this;
    }

    /**
     * @var array<int,Filter>
     */
    private array $filters = [];

    public function filter(Filter $filter): static
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function includeSoftDeleted(): static
    {
        $this->includeDeleted = true;
        return $this;
    }

    private static function createAlias(int $index): string
    {
        return '_' . $index;
    }

    private function buildQuery(): Builder
    {
        $query = DB::table($this->entityReflection->getTableName())
            ->select($this->entityReflection->getSelectColumns());

        // TODO: add support for soft-deletes
        // TODO: add support for circumventing soft-deletes
        if (!$this->includeDeleted) {
            foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
                $columnName = $this->entityReflection->getMappers()->get($property)->getColumnNames()[0];
                $this->filter(new IsNull($columnName));
            }
        }

        foreach ($this->filters as $filter) {
            $relations = $filter->getRelations();
            if (count($relations) > 0) {
                $alias = '';
                $repo = $this;
                foreach ($relations as $relation) {
                    if (!$repo->with->has($relation)) {
                        $repo->with($relation);
                    }
                    $var = $repo->with->get($relation);
                    $repo = $var->repository;
                    $alias = $var->alias . $alias;
                }
            } else {
                $alias = $this->entityReflection->getTableName();
            }
            $filter->applyFilter($query, $alias);
        }

        foreach ($this->with as $linkedRepo) {
            self::applyJoin($query, $linkedRepo, $this->entityReflection->getTableName(), $linkedRepo->alias);
        }

        return $query;
    }

    /**
     * @template S of object
     * @param  Builder  $query
     * @param  LinkedRepository<S,object>  $linkedRepository
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    private static function applyJoin(Builder $query, LinkedRepository $linkedRepository, string $from, string $to): void
    {
        $linkedRepository->linker->join($query, $from, $to, $linkedRepository->repository->filters);
        foreach ($linkedRepository->repository->with as $subLinkedRepo) {
            self::applyJoin($query, $subLinkedRepo, $to, $subLinkedRepo->alias . $to);
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
            $reset = true;
        }

        foreach ($this->with as $linkedRepo) {
            $newAlias = $linkedRepo->alias . $alias;
            $entity = $linkedRepo->repository->resolve($item, $newAlias, $reset);
            $linkedRepo->linker->link($this->resolvingEntity, $entity);
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
