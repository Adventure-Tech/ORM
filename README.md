# ORM
A repository-based and encapsulated ORM built on top of Eloquent's query builder

# Table of contents
| Chapter                                       | Content                                                                                                                                                                                                                                                           |
|-----------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Entities](#entities)                         | Entities are at the core of the ORM. They define not only data-transfer objects, but also form the basis of how data is retrieved by the repositories, how data is inserted by the persistence managers, and even provide default factories for testing purposes. |
| [Repositories](#repositories)                 | Repositories provide a mechanism to retrieve data from the database.                                                                                                                                                                                              |
| [Persistence Managers](#persistence-managers) | Where repositories enable reading of data from the database, persistence managers enable writing to the database.                                                                                                                                                 |
| [Factories](#factories)                       | Factories are a very convenient way to create test data via the entities.                                                                                                                                                                                         |
| [Extending the ORM](#extending-the-orm)       | The ORM is highly extendable.                                                                                                                                                                                                                                     |

## Entities
Entities are at the core of the ORM. They define not only data-transfer objects, but also form the basis of how data is retrieved by the repositories, how data is inserted by the persistence managers, and even provide default factories for testing purposes.

Consider for example a user on a blog post website. The following could be an example of an entity for a user:
```php
#[Entity]
class User
{
    use WithTimestamps;
    use WithSoftDeletes;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $name;

    #[HasMany(targetEntity: Post::class, foreignKey: 'author')]
    public Collection $posts;

    #[BelongsToMany(
        targetEntity: User::class,
        pivotTable: 'friends',
        originForeignKey: 'a_id',
        targetForeignKey: 'b_id'
    )]
    public Collection $friends;
}
```

This example show-cases several important features of entities:

- Each entity needs to be annotated with the `#[Entity]` annotation. The table name is usually inferred from the class name as the plural snake case, e.g. `my_posts` for the `MyPost` entity. The entity annotation allows customisation of the table name, the repository and the factory.
```php
#[Entity(table: 'my_table_name', repository: MyRepository::class, factory: MyFactory::class)]
```

- Further, each entity must have a single integer property annotated with `#[Id]`.

- Next the `#[Column]` annotation enables mapping of a database column to an object property.

- Finally, there are several annotations to map relation between entities, which will be elaborated on below.

### Column Attributes and Mappers
```php
#[Column(name: 'my_column_name')]
public string $firstName
```
The default `#[Column]` annotation infers the database column name based on the property name it is mapped to. For example `$firstName` is assumed to be found in the database column `first_name`. This can be overridden by an optional parameter for the annotation, e.g. `#[Column(name: 'my_column_name')]`.

#### Property Types

The entity properties must always be correctly typed. In particular, nullable columns should have nullable types. Further, default values should be set appropriately, except for nullable columns which have an implicit null default. For example consider the following valid declaration:
```php
// nullable (both identical):
public ?string $foo;
public ?string $foo = null;

// non-nullable column with default value:
public string $foo = 'default';

// non-nullable column without default value:
public string $foo;
```

Note that union and intersection types are not supported. The following are invalid:
```php
// INVALID: missing type hint
public $foo;

// INVALID: union types not supported
public TypeA|TypeB $foo;

// INVALID: intersection types not supported
public TypeA&TypeB $foo;
```

The basic `#[Column]` annotation supports the following types:

- `CarbonImmutable` utilising `toIso8601String`/`CarbonImmutable::parse`
- `array` utilising `json_encode`/`json_decode` and associative arrays
- any other type that is natively supported by Laravel's query builder, i.e. `int`, `float`, `string` and `bool`

#### Getters and Setters
Properties must be either public or provide appropriately named getters and setters. The naming convention is illustrated in the following example:
```php
class MyEntity
{
    #[Column]
    public string $foo;

    #[Column]
    private string $bar;

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $value): void
    {
        $this->foo = $value;
    }
}
```

Note that if a property is public but also has a getter and setter, the ORM will prioritise the getter/setter.

Also, be careful to ensure that getters/setters don't break the serialization to and from the database. Most data transformation should probably live in [custom mappers](#column-attributes-and-mappers), while getters/setters can manage any side effects, such as setting additional properties on the entity for convenience.

### Managed Columns and Soft-Deletes
There are built-in annotations for the managed datetime columns `#[CreatedAt]` and `#[UpdatedAt]`. Object properties annotated with these annotations are completely managed by the ORM and cannot be manually set or updated.

There is also a `#[DeletedAt]` annotation, which marks the object property as a deleted-at timestamp. Once the annotation is present on an entity, the ORM will treat all entities with non-null deleted-at columns as deleted. In the repository such soft-deleted entities are automatically excluded unless explicitly included via `->includeSoftDeleted()`, and the persistence manager's `->delete()` method will set the deleted at column instead of actually deleted the database record. Soft-deleted entities can be restored via the `->restore()` method of the persistence manager.

The ORM provides two traits for convenience: `WithDatetimes` and `WithSoftDeletes`, which mirror Laravel's `->timestamps()` and `->softDeletes()` methods.

### Relation Attributes
An important part of any ORM is the ability to map relations between entities. This is done via relation attributes which are named in line with Eloquent's relations.

#### BelongsTo
```php
class FooEntity
{
    #[BelongsTo(foreignKey: 'bar_entity_id')]
    public BarEntity $bar;
}
```
`BelongsTo` relations signify the owning side of a one-to-one or one-to-many relation. "Owning-side" in this case refers to the foreign key that resides on the table of the entity which has the `BelongsTo` relation declared.

The `BelongsTo` annotation allows the foreign key to be customised, but provides a default based on the property type, e.g. `BarEntity` would lead to `bar_entity_id`.

#### HasMany
```php
class FooEntity
{
    #[HasMany(targetEntity: BarEntity::class, foreignKey: 'bar_entity_id')]
    public Collection $bars;
}
```
`HasMany` relations signify a many-to-one relation. The other side of the `HasMany` relation, its target entity, is a `BelongsTo` relation.

The `HasMany` annotation requires the target entity to be provided and allows the foreign key on the target entity's database table to be customised. A default similar to the `BelongsTo` relation is used if no foreign key is provided.

An important note is that the `HasMany` relation requires the property to be typed as a `Illuminate\Support\Collection`.

#### HasOne
```php
class FooEntity
{
    #[HasOne(foreignKey: 'foo_entity_id')]
    public BarEntity $bar;
}
```
`HasOne` relations signify the non-owning side of a one-to-one relation. They are very similar to a `HasMany` relation, but have an additional unique constraint on the foreign key on the owning database table.

Similar to the `BelongsTo` relation, an optional foreign key can be provided. However, unlike the `BelongsTo` relation, the default foreign key of a `HasOne` relation is based on the entity class name itself, e.g. `MyEntity` would lead to `my_entity_id`.

#### BelongsToMany
```php
class FooEntity
{
    #[BelongsToMany(
        targetEntity: BarEntity::class,
        pivotTable: 'foo_bar_pivot_table',
        originForeignKey: 'foo_entity_id',
        targetForeignKey: 'bar_entity_id'
    )]
    public Collection $bars;
}
```
Many-to-many relations are encoded by the `BelongsToMany` annotation. On the database these relations are encoded by a pivot table, whose only columns are two foreign key columns which form a compound primary key.

The `BelongsToMany` annotation requires the target entity and pivot table to be provided. The two foreign keys can be customised but are inferred by default from the class name and target entity similar to the `HasMany` and `BelongsTo` relations, respectively.

## Repositories
Repositories provide a mechanism to retrieve data from the database. To retrieve a repository you have to use the static `Repository::new()` method. This returns the repository set in the `#[Entity(repository: MyRepository::class)]` annotation or a generic `Repository` instance.

This instance exposes several methods inspired by Eloquent's methods.
```php
$repository = Repository::new(FooEntity::class);

// get all entities (matching any applied filters)
$repository->get();

// find specific entity by ID - return null if not found
$repository->find($id);

// find specific entity by ID - throw exception if not found
$repository->findOrFail($id);
```

### Loading Relations
By default, repositories only load the data mapped in the entity they are based on. Any relations need to be loaded explicitly by calling the `with()` method providing the property name of the relation to be loaded.
```php
$fooEntity = Repository::new(FooEntity::class)
    ->with('bar')
    ->find($id);

$fooEntity->bar // is now loaded
```

Unloaded relations result in non-initialised properties on the entity. This means that an error would be thrown if one attempts to access an unloaded relation, e.g. `$fooEntity->bar` without calling `->with('bar')`.

As an optional second argument for the `with()` method a function can be provided which gives access to the repository of the loaded relation:
```php
Repository::new(FooEntity::class)
    ->with('bar', function(Repository $barRepository) {
        // can call filters or other things on $barRepository here
    })
```

### Filters
Repositories allow filtering of the results via the `filter()` method. This method accepts a single argument: an instance implementing the `Filter` interface. The ORM provides a few filters out of the box inspired by Eloquent's filter methods:
```php
new Where('column', IS::EQUAL, 'value');
new WhereIn('column', ['a', 'b', 'c']);
new WhereNull('column');
new WhereNot('column', IS::GREATER_THAN_OR_EQUAL_TO, 3);
new WhereNotIn('column', [1, 2, 3]);
new WhereNotNull('column');
new WhereColumn('column', IS::NOT_EQUAL, 'other_column');
```
Note that these refer to database column names, not entity property names.

There are also two filters which allow to chain multiple filters either via AND or via OR. Note that multiple calls to the `filter()` method are chained as AND.
```php
new AndWhere($filterA, $filterB, ...);
new OrWhere($filterA, $filterB, ...);
```

#### Filtering within loaded Relations
When loading relations filters can be applied both to the parent repository and the loaded repository. Further, filters get access across the chain of loading repositories via a DSL inspired by unix path syntax: e.g. `../relation/column`. Note that relations that are referenced in filters must be loaded.
```php
Repository::new(FooEntity::class)
    ->with('bar', function (Repository $barRepository) {
        $barRepository->with('baz', function (Repository $bazRepository) {
            $bazRepository
                ->filter(new Where('../../column_on_foo', IS::EQUAL, 'foo_value'))
                ->filter(new Where('../../bam/column_on_bam', IS::EQUAL, 'bam_value'))
                ->filter(new Where('../column_on_bar', IS::EQUAL, 'bar_value'))
                ->filter(new Where('column_on_baz', IS::EQUAL, 'baz_value'));
        })
    })
    ->with('bam')
```

It is important to distinguish the following two cases:

- Applying a filter within a loaded relation. This does not at all affect the parent entities retrieved, only which entities are loaded. In the following example all `FooEntity` are retrieved, but only `BarEntity` are loaded which match the filter.
```php
Repository::new(FooEntity::class)
    ->with('bar', function (Repository $barRepository) {
        $barRepository->filter(new Where('column', IS::EQUAL, 'value'));
    })
```
- Applying a filter on the parent repository which references the loaded relation. This on the other hand restrict with parent entities are loaded. In the following example only `FooEntity` matching the filter are loaded, but for each `FooEntity` all `BarEntity` are loaded.
```php
Repository::new(FooEntity::class)
    ->with('bar')
    ->filter(new Where('bar/column', IS::EQUAL, 'value'))
```

## Persistence Managers
Where repositories enable reading of data from the database, persistence managers enable writing to the database. While a generic repository is provided for all entities out of the box, persistence managers must be defined manually for each entity.
```php
class FooPersistenceManager extends PersistenceManager
{
    protected static string $entity = FooEntity::class;
}
```

This enables using architectural tests to limit access to write functionality by restricting the usage of the relevant persistence manager.

### Inserting
To insert a record to the database, a new entity instance needs to be passed to the static `insert` method with all non-nullable column properties except the ID property set. If the ID is set or any property is missing, an exception is thrown.

Note that owning relations, such as the `BelongsTo` relation, must be set as well if they are non-nullable. In this context "set" means that the linked entity must have a valid ID set on them.

The static `insert` method updates the entity instance passed to it, setting the ID value on it and any managed columns. Note that setting the value of a managed column gets ignored and overridden by the persistence manager.

```php
// Create entity instance
$fooEntity = new FooEntity;
$fooEntity->column = 'value';
$fooEntity->createdAt = now()->subDay(); // gets ignored

// Insert via persistence manager
FooPersistenceManager::insert($fooEntity);

// Entity instance got updated
$fooEntity->id;        // ID is now set
$fooEntity->createdAt; // managed columns are also set
```

### Updating
To update a record on the database a entity instance with the ID set needs to be passed to the static `update`. If the ID is missing or any non-nullable column is missing, an exception is thrown.

Similar to the `insert` method, the `update` method automatically handles managed columns, such as the `updated_at` column.

```php
// Retrieve entity (with set ID)
$fooEntity = Repository::new(FooEntity::class)->find(1);

// Update properties as required
$fooEntity->column = 'updated_value';
$fooEntity->updatedAt = now()->subDay(); // gets ignored

// Persist updates via persistence manager
FooPersistenceManager::update($fooEntity);
```

Note that updating effectively is a `PUT` operation and not a `PATCH`, therefore all changes to the entity will be persisted.

### Deleting
To delete an entity, simply pass an entity with its ID set to the static `delete` method. This then either deletes the record from the database or sets the soft-delete column if the entity has soft-deletes enabled.


You can undo soft-deletes by calling the `restore()` method, or force-delete permanently via the `forceDelete()` method.


```php
// Soft-delete via persistence manager
FooPersistenceManager::delete($fooEntity);

// The soft-delete column is now non-null
$fooEntity->deletedAt !== null;

// Can restore soft-deleted records
FooPersistenceManager::restore($fooEntity);

// Now again have
$fooEntity->deletedAt === null;

// Can also permanently delete the record
FooPersistenceManager::forceDelete($fooEntity);
```

### Many-to-many Relations
Finally, to insert/delete records on pivot tables of many-to-many relations (`BelongsToMany`) use the static `attach`/`detach` methods:
```php
// Assume FooEntity has a BelongsToMany property called bar linking to BarEntities.
// Then can attach via
FooPersistenceManager::attach($fooEntity, [$barEntityA, $barEntityB], 'bar');

// And detach via
FooPersistenceManager::detach($fooEntity, [$barEntityA, $barEntityB], 'bar');
```

## Factories
Factories are a very convenient way to create test data via the entities.
Similar to repositories, the factories are retrieved by the static `Factory::new()` method. This returns the factory set in the `#[Entity(factory: MyFactory::class)]` annotation or a generic `Factory` instance.

The factory then provides methods similar to Laravel's factories, such as
```php
$factory = Factory::new(FooEntity::class);

// Set state for this instance of the factory
$factory->state([
    'property' => 'original value',
]);

// Can override the state during the create process
fooEntity = $factory->create([
    'property' => 'another value'
]);
$fooEntity->column === 'another value';

// Overriding state in the create method does not affect the factory instance
fooEntity = $factory->create();
$fooEntity->column === 'original value';

// Can also create multiple instances at once
$collection = $factory->createMultiple(5);
$collection->count() === 5;
```

Similar to setting properties in the `state()` and `create()` methods, you can also set owning relations such as `BelongsTo` relations. These can be set both to specific instances or to factory instances which may have their own state set. Relations set to factories are resolve when the `create()` or `createMultiple` methods are called.
```php
$barEntity = Factory::new(BarEntity::class)->create(['property' => 'A']);

$bazFactory = Factory::new(BazEntity::class)->state(['property' => 'B']);

Factory::new(FooEntity::class)->state([
    'bar' => $barEntity,
    'baz' => $bazFactory,
])->createMultiple(3);

// This creates the following number of entities on the database
Repository::new(FooEntity::class)->get()->count() === 3;
Repository::new(BarEntity::class)->get()->count() === 1;
Repository::new(BazEntity::class)->get()->count() === 3;
```

### Custom Factories
The generic factory provides random default values based on the property type in the entity definition. These defaults are as follows:

- `int` results in `$faker->randomNumber()`
- `float` results in `$faker->randomFloat()`
- `string` results in `$faker->word()`
- `bool` results in `$faker->randomElement([true, false])`
- `CarbonImmutable` results in `CarbonImmutable::parse($faker->dateTime())`
- `array` results in `[]`
- nullable columns are set to `null`


As mentioned above the default factory can be overridden in the `#[Entity(factory: MyFactory::class)]` annotation. The custom factory must extend the base `Factory` and can override the protected `define()` method similar to Eloquent's factories. The main difference to Eloquent is that not all columns need to be mapped, as all non-mapped columns are resolved to the generic defaults listed above. And example of a custom factory might look like

```php
class MyFactory extends Factory
{
    protected function define(): array
    {
        return [
            // Have access to a faker instance
            'text' => $this->faker->paragraph(),

            // Can set owning relations to instances or factories
            'relation' => Factory::new(FooEntity::class)->state(['property' => 'value']),

            // The following is redundant
            'myProperty' => $this->faker->randomNumber(),
        ];
    }
}
```


## Extending the ORM
The ORM is highly extendable. Most concepts are encoded in interfaces and simply providing your own implementations allows to include new functionality into the ORM.

### Custom Filters
You can easily add new filters to the ORM by implementing the `Filter` interface:
```php
readonly class FooFilter implements Filter
{
	public function __construct(
		private string $column,
		// any other data needed
	) {}

	public function applyFilter(
		JoinClause|Builder $query,
		LocalAliasingManager $aliasingManager
	): void
	{
		$column = $aliasingManager->getQualifiedColumnName($this->column);
		// simply apply the wanted where clauses to the $query builder
	}
}
```
Ensure to correctly obtain the column name by using the `LocalAliasingManager` (see [Aliasing](#aliasing) for details).

### Column Attributes and Mappers
The process of mapping database columns to entity properties consists of two parts:

1) A `ColumnAnnotation` that resolves to a mapper via the `getMapper` function and allows any relevant info to be passed in via the constructor of the annotation
2) A `Mapper` that provides `serialize` and `deserialize` functions

#### Existing Mappers
The `#[Column]` annotation resolves to the following mappers:

- `array` types resolve to the `JSONMapper` based on a simple `json_encode()`/`json_decode()` logic
- `CarbonImmutable` types resolve to the `DatetimeMapper` based on `CarbonImmutable::parse()`
- all other types get resolved to the `DefaultMapper`, which assumes the query builder mapped the result correctly  (valid e.g. for `bool`, `int`, `string`, `float`)

There is a further `DatetimeTZMapper` available with a dedicated `DatetimeTZColumn` annotation. This enables storing of the timezone in a separate varchar column, and correctly sets the timezone in the `CarbonImmutable` instance.

There are two ways of providing a custom mapper:

##### 1. Custom `SimpleMapper` via the `#[Column]` annotation
The `#[Column]` annotation accepts an optional argument, which is the class name for a mapper implementing the `SimpleMapper` interface.
```php
#[Entity]
class FooEntity
{
	#[Column(mapper: FooSimpleMapper::class)]
	public FooType $foo;
}
```
Where the `FooSimpleMapper` looks something like
```php
readonly class FooSimpleMapper implements SimpleMapper
{
	use WithDefaultMapperMethods;

//    WithDefaultMapperMethods provides the following sensible defaults:
//
//    public function __construct(private string $name)
//    {
//    }
//
//    public function getColumnNames(): array
//    {
//        return [ $this->name ];
//    }

	public function serialize(mixed $value): array
	{
		// $value is instance of FooType
		return [
			$this->name => $value->convertFooToString(),
		];
	}

	public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): FooType
	{
		$column = $aliasingManager->getSelectedColumnName($this->name);
		$value = $item->{$column};
		return new FooType($value);
	}
}
```
See [Aliasing](#aliasing) for an explanation of the `LocalAliasingManager`.

Note that as PHP does not support default implementations for interfaces, the default implementations are provided via the `WithDefaultMapperMethods` trait instead.

Also, care needs to be taken to ensure that mappers and getters/setters are compatible!

#### 2. More general custom `Mapper` with custom `ColumnAnnotation`
There are use cases where we might want to parametrise more than the column name in the mapper. Or alternatively some mappers might combine multiple database columns into a single value (e.g. the `DatetimeTZMapper`).

To implement such a case yourself you need to provide both a `ColumnAnnotation`
```php
readonly class FooColumn implements ColumnAnnotation
{
	public function __construct(
		private ?string $name = null,
		private array $myExtraData = [],
	) {}

	public function getMapper(ReflectionProperty $property): FooMapper
	{
		return new FooMapper(
			$this->name ?? 'default_foo_column_name',
			$this->myExtraData
		);
	}
}
```
and a `Mapper`:
```php
readonly class FooMapper implements Mapper
{
	public function __construct(
		private string $name,
		private array $myExtraData
	) {}

    public function getColumnNames(): array
    {
        return [ $this->name, $this->myExtraData['extra_column'] ];
    }

	public function serialize(mixed $value): array
	{
		// $value is instance of FooType
		return [
			$this->name                        => $value->convertFooToString(),
			$this->myExtraData['extra_column'] => $value->convertFooToExtra(),
		];
	}

	public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): FooType
	{
		$column = $aliasingManager->getQualifiedColumnName($this->name);
		$value = $item->{$column};

		$extraColumn = $aliasingManager->getQualifiedColumnName($this->myExtraData['extra_column']);
		$extra = $item->{$extraColumn};

		return new FooType($value, $extraValue);
	}
}
```

You can then use the custom `ColumnAnnotation` just as you would the normal `#[Column]` annotation:

This is then utilised in the entity by
```php
#[Entity]
class FooEntity
{
	#[FooColumn(name: 'foo_value', extraData: ['extra_column' => 'foo_extra'])]
	public FooType $foo;
}
```


### Custom Managed Columns / Soft-Deletes
Again you can in theory provide your own managed-columns (not just for datetimes) and soft-delete annotations (must be a datetime column). All you have to do is implement the according interfaces `ManagedColumnAnnotation` or `SoftDeleteAnnotation`, respectively.

### Custom Relations and Linkers
Similar to `ColumnAnnotations` and `Mappers`, there is a pair of interfaces for defining custom relations: the `Relation` annotation and the actual `Linker`. While possible to provide custom relations, this is anticipated to be a very rare requirement. Hence, we omit the details here, but we encourage to have a look at the existing implementations and have a play around!

### Aliasing
The way the ORM works is by compiling any request for data by the Repository into a single SQL query with a join for each loaded relationship. When executed, the query builder then populates a `stdClass` object with the data, where it simply overwrites any values which have the same column name (e.g. an `id` column on multiple joined database tables).

To avoid this the ORM aliases all joined tables and all selected columns.

Therefore, whenever we interact with either the query itself (e.g. filters and linkers) or the `stdClass` retrieved by the query builder we need to use the appropriately aliased column names. This is made easy by the `LocalAliasingManager`, which exposes several methods.

For example the consider the following query:
```php
Repository::new(FooEntity::class)
	->with('bar', function(Repository $repo) use ($barFilter) {
		$repo->filter($barFilter);
	})
	->filter($fooFilter);
```
Ignoring the two filters, this will generate SQL looking something like:
```sql
SELECT
    "foo_table"."id" AS "foo_tableid",
    "_0_"."id" AS "_0_id",
FROM
    "foo_table"
    LEFT JOIN "bar_table" AS "_0_" ON "_0_"."foo_id" = "foo_table"."id"
```
In the `$fooFilter` the `LocalAliasingManager` will return the following:
```php
$localAliasingManager->getQualifiedColumnName('id')     === 'foo_table.id';
$localAliasingManager->getSelectedColumnName('id')      === 'foo_tableid';
$localAliasingManager->getQualifiedColumnName('bar/id') === '_0_.id';
$localAliasingManager->getSelectedColumnName('bar/id')  === '_0_id';
$localAliasingManager->getAliasedTableName()            === 'foo_table';
```
In the `$barFilter` on the other hand the `LocalAliasingManager` will return the following:
```php
$localAliasingManager->getQualifiedColumnName('id')    === '_0_.id';
$localAliasingManager->getSelectedColumnName('id')     === '_0_id';
$localAliasingManager->getQualifiedColumnName('../id') === 'foo_table.id';
$localAliasingManager->getSelectedColumnName('../id')  === 'foo_tableid';
$localAliasingManager->getAliasedTableName()           === '_0_';
```
