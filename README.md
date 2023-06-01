# ORM
A performant and encapsulated ORM built on top of Eloquent's query builder

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

Each entity needs to be annotated with the `#[Entity]` annotation, which also enables setting a custom repository and custom factory. Further, each entity must have a single integer property annotated with `#[Id]`.

Next the `#[Column]` annotation enables mapping of a database column to an object property.

Finally, there are several annotations to map relation between entities.

### Column Attributes and Mappers
```php
#[Column(name: 'custom_column_name')]
public string $firstName
```
The default `#[Column]` annotation infers the database column name based on the property name it is mapped to. For example `$firstName` is assumed to be found in the database column `first_name`. This can be overridden by an optional parameter for the annotation, e.g. `#[Column(name: 'custom_column_name')]`.

The object properties need to be public for the persistence manager to function. They should also always be properly type-hinted, including nullability. Finally, their default value should be set appropriately, including for nullable columns.
```php
public ?string $nullableColumn = null;
public string $nonNullableColumWithDefault = 'default';
public string $nonNullableColumWithoutDefault;
```

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

### Managed Columns and Soft-Deletes
There are built-in annotations for the managed datetime columns `#[CreatedAt]` and `#[UpdatedAt]`. Object properties annotated with these annotations are completely managed by the ORM and cannot be manually set or updated.

There is also a `#[DeletedAt]` annotation, which marks the object property as a deleted at timestamp. Once the annotation is present on an entity, the repository will exclude all entities with non-null deleted at columns unless explicitly included via `->includeSoftDeleted()`, and the persistence manager's `->delete()` method will set the deleted at column instead of actually deleted the database record.

The ORM provides two traits for convenience: `WithDatetimes` and `WithSoftDeletes`, which mirror Eloquent's `->timestamps()` and `->softDeletes()` methods.

Again you can in theory provide your own managed-columns (not just for datetimes) and soft-delete annotations (must be a datetime column). All you have to do is implement the according interfaces `ManagedColumnAnnotation` or `SoftDeleteAnnotation`, respectively.

### Relation Attributes and Linkers
#### BelongsTo
#### HasMany
#### HasOne
#### BelongsToMany
#### Custom Relations

## Repositories
### Loading Relations
### Filters
### Custom Repositories

## Persistence Managers

## Factories
