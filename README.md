# ORM
A repository-based and encapsulated ORM built on top of Eloquent's query builder

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
public string $foo;

private string $bar;

public function getFoo(): string
{
    return $this->foo;
}

public function setFoo(string $value): void
{
    $this->foo = $value;
}
```

Note that if a property is public but also has a getter and setter, the ORM will prioritise the getter/setter.

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
### Loading Relations
### Filters
### Custom Repositories

## Persistence Managers

## Factories

## Customising the ORM
The ORM is highly extendable. Most concepts are encoded in interfaces and simply providing your own implementations allows to include new functionality into the ORM.

### Column Attributes and Mappers
The process of mapping database columns to entity properties consists of two parts:

1) A `ColumnAnnotation` that resolves to a mapper via the `getMapper` function and allows any relevant info to be passed in via the constructor of the annotation
2) A `Mapper` that provides `serialize` and `deserialize` functions

#### Behind-the-scenes
The `#[Column]` annotation resolves to the following mappers:

- `array` types resolve to the `JSONMapper` based on a simple `json_encode`/`json_decode` logic
- `CarbonImmutable` types resolve to the `DatetimeMapper` based on `CarbonImmutable::parse`
- all other types get resolved to the `DefaultMapper`, which assumes the query builder mapped the result correctly

There is a further `DatetimeTZMapper` available with a dedicated `DatetimeTZColumn` annotation, which stores the timezone in a separate varchar column.

#### Custom Column Mappers
The ORM is very extensible. All that needs to be done is to implement the `ColumnAnnotation` interface and provide a suitable `getMapper()` function. This gets access to the `ReflectionProperty` of the object property which is annotated with the custom column annotation. It needs to return a mapper implementing the `Mapper` interface. This requires the following methods:

- `getPropertyName` – returning the name of the object property
- `getColumnNames` – the names of columns that need to be selected from the database
- `isInitialized` – a function checking
- `serialize` – a function serializing the object property value to a list of values to be inserted into the database
- `deserialize` – a function deserializing a list of values retrieved from the database to be set on the object

### Custom Managed Columns / Soft-Deletes
Again you can in theory provide your own managed-columns (not just for datetimes) and soft-delete annotations (must be a datetime column). All you have to do is implement the according interfaces `ManagedColumnAnnotation` or `SoftDeleteAnnotation`, respectively.

### Custom Relations and Linkers
