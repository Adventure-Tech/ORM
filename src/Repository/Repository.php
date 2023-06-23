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
    private ?int $resolvingId = null;
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
     * @param  int  $id
     *
     * @return T|null
     */
    public function find(int $id)
    {
        $data = $this->buildQuery()
            ->where($this->localAliasingManager->getQualifiedColumnName($this->entityReflection->getId()), $id)
            ->get();
        return $this->mapToEntities(
            $data
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

    /**
     * @param  string         $relation
     * @param  callable|null  $callable
     *
     * @return $this<T>
     */
    public function with(string $relation, callable $callable = null): static
    {
        if (!$this->entityReflection->getLinkers()->has($relation)) {
            throw new InvalidRelationException('Invalid relation used in with clause [tried to load relation "' . $relation . '"]');
        }

        /** @var Linker<T,object> $linker */
        $linker = $this->entityReflection->getLinkers()->get($relation);

        $repository = self::internalNew(
            $linker->getTargetEntity(),
            $this->aliasingManager,
            $this->localRoot . '/' . $relation,
        );
        $this->aliasingManager->addRelation(
            $this->localRoot . '/' . $relation,
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
        $result = Collection::empty();
        foreach ($data as $item) {
            if ($entity = $this->resolve($item)) {
                $result[] = $entity;
            }
        }
        $this->resetResolver();
        return $result;
    }

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
        if ($id !== $this->resolvingId) {
            $this->resolvingId = $id;
            $this->resolvingEntity = $this->entityReflection->newInstance();
            foreach ($this->entityReflection->getMappers() as $property => $mapper) {
                EntityAccessorService::set(
                    $this->resolvingEntity,
                    $property,
                    $mapper->deserialize($item, $this->localAliasingManager)
                );
            }
            $reset = true;
        }

        foreach ($this->with as $linkedRepo) {
            $entity = $linkedRepo->repository->resolve($item, $reset);
            $linkedRepo->linker->link($this->resolvingEntity, $entity);
        }

        EntityAccessorService::initEntity($this->resolvingEntity);

        return $reset && isset($this->resolvingEntity) ? $this->resolvingEntity : null;
    }

    private function resetResolver(): void
    {
        $this->resolvingId = null;
        unset($this->resolvingEntity);
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
