<?php

/**
 *
 */

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\AliasingManagement\AliasingManager;
use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
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
    private AliasingManager $aliasingManager;

    private string $localRoot;

    private LocalAliasingManager $localAliasingManager;

    /**
     * @var array<int|string,Filter>
     */
    private array $filters = [];

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
        $this->aliasingManager = new AliasingManager(
            $this->entityReflection->getTableName(),
            $this->entityReflection->getSelectColumns()
        );
        $this->localRoot = $this->entityReflection->getTableName();
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
        $repository->localRoot = $this->localRoot . '/' . $relation;
        $repository->localAliasingManager = new LocalAliasingManager($this->aliasingManager, $repository->localRoot);
        $repository->aliasingManager = $this->aliasingManager;
        $this->aliasingManager->addRelation(
            $this->localRoot . '/' . $relation,
            $repository->entityReflection->getSelectColumns()
        );
        if ($callable) {
            $callable($repository);
        }
        $repository->applySoftDeleteFilters();

        $this->with->put(
            $relation,
            new LinkedRepository($linker, $repository)
        );

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
            $subLinkedRepo->repository->applyJoin($query, $subLinkedRepo);
        }
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
            // TODO: when does this occur?
            return null;
        }
        if ($id !== $this->resolvingId) {
            $this->resolvingId = $id;
            $this->resolvingEntity = $this->entityReflection->newInstance();
            foreach ($this->entityReflection->getMappers() as $property => $mapper) {
                $this->resolvingEntity->{$property} = $mapper->deserialize($item, $this->localAliasingManager);
            }
            $reset = true;
        }

        foreach ($this->with as $linkedRepo) {
            $entity = $linkedRepo->repository->resolve($item, $reset);
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

    private function applySoftDeleteFilters(): void
    {
        if (!$this->includeDeleted) {
            foreach ($this->entityReflection->getSoftDeletes() as $property => $softDelete) {
                /** @var Mapper<mixed> $mapper */
                $mapper = $this->entityReflection->getMappers()->get($property);
                // TODO: remove this from mapper
                $columnName = $mapper->getColumnNames()[0];
                $this->filter(new WhereNull($columnName));
            }
        }
    }
}
