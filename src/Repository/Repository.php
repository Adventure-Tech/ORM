<?php

/**
 *
 */

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\EntityAccessorService;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Repository\Filters\Filter;
use AdventureTech\ORM\Repository\Filters\WhereNull;
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
    private int|string|null $resolvingId = null;
    /**
     * @var T
     */
    private object $resolvingEntity;
    private bool $includeDeleted = false;
    private readonly LocalAliasingManager $localAliasingManager;
    /**
     * @var array<int,Filter>
     */
    private array $filters = [];
    /**
     * @var array<string,LinkedRepository<T,object>>
     */
    private array $with = [];

    /**
     * @template E of object
     * @param  class-string<E>  $class
     *
     * @return Repository<E>
     */
    public static function new(string $class): Repository
    {
        return self::internalNew($class);
    }

    /**
     * @template E of object
     * @param  class-string<E>  $class
     * @param  AliasingManager|null  $aliasingManager
     * @param  string|null  $localRoot
     * @return Repository<E>
     */
    private static function internalNew(string $class, AliasingManager $aliasingManager = null, string $localRoot = null): Repository
    {
        $entityReflection = EntityReflection::new($class);
        $repository = $entityReflection->getRepository() ?? self::class;
        if (is_null($aliasingManager)) {
            $aliasingManager = new AliasingManager(
                $entityReflection->getTableName(),
                $entityReflection->getSelectColumns()
            );
        }
        if (is_null($localRoot)) {
            $localRoot = $entityReflection->getTableName();
        }
        return new $repository($entityReflection, $aliasingManager, $localRoot);
    }

    /**
     * @param  EntityReflection<T>  $entityReflection
     * @param  AliasingManager  $aliasingManager
     * @param  string  $localRoot
     */
    private function __construct(
        private readonly EntityReflection $entityReflection,
        private readonly AliasingManager $aliasingManager,
        private readonly string $localRoot
    ) {
        $this->localAliasingManager = new LocalAliasingManager($this->aliasingManager, $this->localRoot);
    }

    /**
     * @return Collection<int,T>
     */
    public function get(): Collection
    {
        return $this->mapToEntities($this->buildQuery()->get());
    }

    /**
     * @param  int|string  $id
     *
     * @return T|null
     */
    public function find(int|string $id)
    {
        $data = $this->buildQuery()
            ->where($this->localAliasingManager->getQualifiedColumnName($this->entityReflection->getId()), $id)
            ->get();
        $data->pluck('_0_id')->dump();

        return $this->mapToEntities(
            $data
        )->first();
    }

    /**
     * @param  int|string  $id
     *
     * @return T
     */
    public function findOrFail(int|string $id)
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

    /**
     * @param  string         $relation
     * @param  callable|null  $callable
     *
     * @return $this<T>
     */
    public function with(string $relation, callable $callable = null): static
    {
        // TODO: refactor to tidy
        if (is_null($callable)) {
            $relations = explode(AliasingManager::SEPARATOR, $relation, 2);
            $relation = $relations[0];
            if (array_key_exists($relation, $this->with)) {
                if (isset($relations[1])) {
                    $this->with[$relation]->repository->with($relations[1]);
                }
                return $this;
            }
            if (isset($relations[1])) {
                $callable = fn(self $repository) => $repository->with($relations[1]);
            }
        }

        if (!$this->entityReflection->getLinkers()->has($relation)) {
            throw new InvalidRelationException('Invalid relation used in with clause [tried to load relation "' . $relation . '"]');
        }

        /** @var Linker<T,object> $linker */
        $linker = $this->entityReflection->getLinkers()->get($relation);

        $repository = self::internalNew(
            $linker->getTargetEntity(),
            $this->aliasingManager,
            $this->localRoot . AliasingManager::SEPARATOR . $relation,
        );
        $this->aliasingManager->addRelation(
            $this->localRoot . AliasingManager::SEPARATOR . $relation,
            $repository->entityReflection->getSelectColumns()
        );
        if ($callable) {
            $callable($repository);
        }
        $repository->applySoftDeleteFilters();

        $this->with[$relation] = new LinkedRepository($linker, $repository);

        return $this;
    }

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

    /**
     * @var array<string,Direction>
     */
    private array $orderBys = [];

    public function orderBy(string $column): static
    {
        $column = $this->localAliasingManager->getQualifiedColumnName($column);
        $this->orderBys[$column] = Direction::ASCENDING;
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        $column = $this->localAliasingManager->getQualifiedColumnName($column);
        $this->orderBys[$column] = Direction::DESCENDING;
        return $this;
    }

    private function buildQuery(): Builder
    {
        $query = DB::table($this->entityReflection->getTableName())
            ->select($this->aliasingManager->getSelectColumns());

        $this->applySoftDeleteFilters();

        foreach ($this->filters as $filter) {
            $filter->applyFilter($query, $this->localAliasingManager);
        }

        foreach ($this->with as $linkedRepo) {
            $this->applyJoin($query, $linkedRepo);
        }

        foreach ($this->getOrderBys() as $column => $direction) {
            $query->orderBy($column, $direction->value);
        }

        return $query;
    }

    /**
     * @template S of object
     * @param  Builder  $query
     * @param  LinkedRepository<S,object>  $linkedRepository
     * @return void
     */
    private function applyJoin(Builder $query, LinkedRepository $linkedRepository): void
    {
        $linkedRepository->linker->join(
            $query,
            $this->localAliasingManager,
            $linkedRepository->repository->localAliasingManager,
            $linkedRepository->repository->filters
        );
        foreach ($linkedRepository->repository->with as $subLinkedRepo) {
            $linkedRepository->repository->applyJoin($query, $subLinkedRepo);
        }
    }

    /**
     * @param  Collection<int|string,stdClass>  $data
     * @return Collection<int,T>
     */
    private function mapToEntities(Collection $data): Collection
    {
        foreach ($data as $item) {
            $this->resolve($item);
        }
        $result = Collection::wrap($this->resolved);
        $this->resetResolver();
        return $result;
    }

    /**
     * @var array<int|string,T>
     */
    private array $resolved = [];

    /**
     * @param  stdClass  $item
     * @param  bool  $reset
     * @return T|null
     */
    private function resolve(stdClass $item, bool $reset = false): ?object
    {
        $id = $item->{$this->localAliasingManager->getSelectedColumnName($this->entityReflection->getId())};
        if (is_null($id)) {
            // occurs when filtering out of relation (../../)
            return null;
        }
        if (!array_key_exists($id, $this->resolved)) {
            $entity = $this->entityReflection->newInstance();
            foreach ($this->entityReflection->getMappers() as $property => $mapper) {
                EntityAccessorService::set(
                    $entity,
                    $property,
                    $mapper->deserialize($item, $this->localAliasingManager)
                );
            }
            $this->resolved[$id] = $entity;
        } else {
            $entity = $this->resolved[$id];
        }

        foreach ($this->with as $linkedRepo) {
            $linkedEntity = $linkedRepo->repository->resolve($item, $reset);
            $linkedRepo->linker->link($entity, $linkedEntity);
        }

        return $entity;
    }

    private function resetResolver(): void
    {
        $this->resolved = [];
        foreach ($this->with as $linkedRepository) {
            $linkedRepository->repository->resetResolver();
        }
    }

    private function applySoftDeleteFilters(): void
    {
        if (!$this->includeDeleted) {
            foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
                /** @var Mapper<mixed> $mapper */
                $mapper = $this->entityReflection->getMappers()->get($property);
                // TODO: remove this from mapper
                $columnName = $mapper->getColumnNames()[0];
                $this->filters[] = new WhereNull($columnName);
            }
        }
    }

    /**
     * @return array<string,Direction>
     */
    private function getOrderBys(): array
    {
        $orderBys = $this->orderBys;
        foreach ($this->with as $linkedRepository) {
            $orderBys = array_merge($orderBys, $linkedRepository->repository->getOrderBys());
        }
        return $orderBys;
    }
}
