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
use AdventureTech\ORM\Exceptions\EntityReflectionException;
use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\Repository\Filters\Filter;
use AdventureTech\ORM\Repository\Filters\WhereNull;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;
use stdClass;

/**
 * @template TEntity of object
 */
class Repository
{
    const LIMIT_OFFSET_SUBQUERY_ALIAS = '_limited';
    const LIMIT_OFFSET_COUNTER = '_dense_rank_number';
    public readonly string $entity;
    private int|string|null $resolvingId = null;
    /**
     * @var TEntity
     */
    private object $resolvingEntity;
    private bool $includeDeleted = false;
    private readonly LocalAliasingManager $localAliasingManager;
    /**
     * @var array<int,Filter>
     */
    private array $filters = [];
    /**
     * @var array<string,LinkedRepository<TEntity,object>>
     */
    private array $with = [];

    /**
     * @var array<string,Direction>
     */
    private array $orderBys = [];
    private ?int $limit = null;
    private ?int $offset = null;

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
     * @param  EntityReflection<TEntity>  $entityReflection
     * @param  AliasingManager  $aliasingManager
     * @param  string  $localRoot
     */
    protected function __construct(
        private readonly EntityReflection $entityReflection,
        private readonly AliasingManager $aliasingManager,
        private readonly string $localRoot
    ) {
        $this->localAliasingManager = new LocalAliasingManager($this->aliasingManager, $this->localRoot);
        $this->entity = $this->entityReflection->getClass();
    }

    /**
     * @return Collection<int|string,TEntity>
     */
    public function get(): Collection
    {
        return $this->mapToEntities($this->buildQuery()->get());
    }

    /**
     * @param  int|string  $id
     *
     * @return TEntity|null
     */
    public function find(int|string $id)
    {
        $this->limit = null;
        $this->offset = null;
        $query = $this->buildQuery()
            ->where($this->localAliasingManager->getQualifiedColumnName($this->entityReflection->getIdColumn()), $id);
        $query = $this->applyLimitAndOffset($query, 1, null);
        return $this->mapToEntities($query->get())->first();
    }

    /**
     * @param  int|string  $id
     *
     * @return TEntity
     */
    public function findOrFail(int|string $id)
    {
        $entity = $this->find($id);
        if (is_null($entity)) {
            throw new EntityNotFoundException('Failed to find entity of type "' . $this->entityReflection->getClass() . '" with id "' . $id . '".');
        }
        return $entity;
    }

    /**
     * @return TEntity|null
     */
    public function first()
    {
        $this->limit = 1;
        return $this->get()->first();
    }

    /**
     * @return TEntity
     */
    public function firstOrFail()
    {
        $entity = $this->first();
        if (is_null($entity)) {
            throw new EntityNotFoundException('Failed to load any entities of type "' . $this->entityReflection->getClass() . '" matching the filter criteria.');
        }
        return $entity;
    }

    /**
     * @codeCoverageIgnore
     * @return void
     */
    #[NoReturn] public function dd(): void
    {
        $this->buildQuery()->dd();
    }

    /**
     * @codeCoverageIgnore
     * @return static
     */
    public function dump(): static
    {
        $this->buildQuery()->dumpRawSql();
        return $this;
    }

    /**
     * @param  string         $relation
     * @param  callable|null  $callable
     *
     * @return $this
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
                $callable = static fn(self $repository) => $repository->with($relations[1]);
            }
        }

        $linker = $this->entityReflection->getLinker($relation);

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

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, Direction $direction): static
    {
        $column = $this->localAliasingManager->getQualifiedColumnName($column);
        $this->orderBys[$column] = $direction;
        return $this;
    }

    public function orderByAsc(string $column): static
    {
        return $this->orderBy($column, Direction::ASCENDING);
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, Direction::DESCENDING);
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

        // NOTE: this needs to be last (this wraps the query in a subquery to utilise the DENSE_RANK window function)
        if (!is_null($this->limit) || !is_null($this->offset)) {
            $query = $this->applyLimitAndOffset($query, $this->limit, $this->offset);
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
     * @return Collection<int|string,TEntity>
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
     * @var array<int|string,TEntity>
     */
    private array $resolved = [];

    /**
     * @param  stdClass  $item
     * @param  bool  $reset
     * @return TEntity|null
     */
    private function resolve(stdClass $item, bool $reset = false): ?object
    {
        $id = $item->{$this->localAliasingManager->getSelectedColumnName($this->entityReflection->getIdColumn())};
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
                foreach ($this->entityReflection->getMappers()[$property]->getColumnNames() as $columnName) {
                    $this->filters[] = new WhereNull($columnName);
                }
            }
        }
    }

    /**
     * @return array<string,Direction>
     */
    private function getOrderBys(): array
    {
        $orderBys = [];
        foreach ($this->with as $linkedRepository) {
            $orderBys[] = $linkedRepository->repository->getOrderBys();
        }
        return array_merge($this->orderBys, ... $orderBys);
    }

    private function applyLimitAndOffset(Builder $query, ?int $limit, ?int $offset): Builder
    {
        $orderBys = [];
        foreach ($this->orderBys as $column => $direction) {
            $sqlDirection = match ($direction) {
                Direction::ASCENDING => '',
                Direction::DESCENDING => ' DESC',
            };
            $orderBys[] = $column . $sqlDirection;
        }

        $idColumn = $this->localAliasingManager->getQualifiedColumnName($this->entityReflection->getIdColumn());
        if (!array_key_exists($idColumn, $orderBys)) {
            $orderBys[] = $idColumn;
        }

        $query->selectRaw('DENSE_RANK () OVER (ORDER BY '
            . implode(', ', $orderBys)
            . ') AS '
            . self::LIMIT_OFFSET_COUNTER);
        $query = DB::query()->fromSub($query, self::LIMIT_OFFSET_SUBQUERY_ALIAS);
        if (!is_null($limit)) {
            $query->where(
                self::LIMIT_OFFSET_SUBQUERY_ALIAS . '.' . self::LIMIT_OFFSET_COUNTER,
                '<=',
                $limit
            );
        }
        if (!is_null($offset)) {
            $query->where(
                self::LIMIT_OFFSET_SUBQUERY_ALIAS . '.' . self::LIMIT_OFFSET_COUNTER,
                '>',
                $offset
            );
        }
        return $query;
    }
}
