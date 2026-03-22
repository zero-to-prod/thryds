# Phase 5: Package Extraction + Multi-Driver Testing

## Goal

Extract the database layer into `zero-to-prod/db`. Wire `Connection::setResolver()` as the single app-side integration point. Test against MySQL, PostgreSQL, and SQLite.

## Part A: Package Structure

```
zero-to-prod/db/
├── composer.json
├── src/
│   ├── Database.php
│   ├── DatabaseConfig.php
│   ├── MigrationDiscovery.php
│   ├── MigrationStatus.php
│   ├── MigrationStatusResolver.php
│   ├── MigrationStatusRow.php
│   ├── Migrator.php
│   ├── RowAccess.php
│   ├── Attributes/
│   │   ├── AddColumn.php
│   │   ├── Column.php
│   │   ├── Connection.php
│   │   ├── ConnectionOption.php
│   │   ├── CreateTable.php
│   │   ├── DeletesFrom.php
│   │   ├── DropColumn.php
│   │   ├── EnvVar.php
│   │   ├── ForeignKey.php
│   │   ├── HasTableName.php
│   │   ├── Index.php
│   │   ├── InsertsInto.php
│   │   ├── Migration.php
│   │   ├── MigrationAction.php
│   │   ├── MigrationsSource.php
│   │   ├── OnDelete.php
│   │   ├── OnUpdate.php
│   │   ├── PersistColumn.php
│   │   ├── PrimaryKey.php
│   │   ├── RawSql.php
│   │   ├── ResolvesTo.php
│   │   ├── SchemaSync.php
│   │   ├── SelectsFrom.php
│   │   ├── Table.php
│   │   ├── Timezone.php
│   │   └── UpdatesIn.php
│   ├── Queries/
│   │   ├── DbCreate.php
│   │   ├── DbDelete.php
│   │   ├── DbRead.php
│   │   ├── DbUpdate.php
│   │   ├── DeleteMigrationQuery.php
│   │   ├── InsertMigrationQuery.php
│   │   ├── Persist.php
│   │   ├── PersistResolver.php
│   │   ├── SelectLastMigrationQuery.php
│   │   ├── SelectMigrationsQuery.php
│   │   ├── Sql.php
│   │   └── Resolvers/
│   │       ├── NowResolver.php
│   │       ├── PasswordHashResolver.php
│   │       └── RandomIdResolver.php
│   ├── Schema/
│   │   ├── Charset.php
│   │   ├── Collation.php
│   │   ├── DataType.php
│   │   ├── DdlBuilder.php
│   │   ├── Driver.php
│   │   ├── Engine.php
│   │   ├── ReferentialAction.php
│   │   ├── SchemaSource.php
│   │   └── SortDirection.php
│   └── Tables/
│       ├── Migration.php
│       └── MigrationColumns.php
└── tests/
```

### Namespace: `ZeroToProd\Db\`

### composer.json

```json
{
    "name": "zero-to-prod/db",
    "description": "Attribute-driven database layer for PHP 8.5 — MySQL, PostgreSQL, SQLite via PDO",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=8.5",
        "ext-pdo": "*",
        "zero-to-prod/data-model": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0",
        "phpstan/phpstan": "^2.0"
    },
    "autoload": {
        "psr-4": { "ZeroToProd\\Db\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "ZeroToProd\\Db\\Tests\\": "tests/" }
    }
}
```

## Part B: Coupling Points to Sever

### 1. `Connection::resolve()` uses `app()`

The only framework coupling in the entire package.

**Solution:** Pluggable resolver with static setter.

```php
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Connection
{
    /** @var null|Closure(class-string): Database */
    private static ?Closure $resolver = null;

    public function __construct(public string $database) {}

    public static function setResolver(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function resolve(string $class): Database
    {
        if (self::$resolver === null) {
            throw new RuntimeException('Connection resolver not configured. Call Connection::setResolver() at boot.');
        }

        $attrs = new ReflectionClass($class)->getAttributes(self::class);
        $db_class = $attrs !== [] ? $attrs[0]->newInstance()->database : Database::class;

        return (self::$resolver)($db_class);
    }
}
```

App-side (one line at boot):
```php
Connection::setResolver(static fn(string $class) => app()->make($class));
```

Test-side:
```php
Connection::setResolver(fn(string $class) => $this->db);
```

### 2. `#[Table]` requires app-specific `TableName` enum

**Solution:** Accept `BackedEnum`:

```php
public function __construct(
    public \BackedEnum $TableName,
    public ?Engine $Engine = null,
    public ?Charset $Charset = null,
    public ?Collation $Collation = null,
) {}
```

`HasTableName::tableName()` reads `->value` — works with any `BackedEnum`.

### 3. Thryds-specific attributes on package files

Strip from package files:

| Attribute | Action |
|-----------|--------|
| `#[Infrastructure]` | Remove — Thryds graph metadata |
| `#[ClosedSet]` | Remove — Thryds graph metadata |
| `#[KeyRegistry]` / `#[KeySource]` | Remove — Thryds graph metadata |

Keep:
| Attribute | Why |
|-----------|-----|
| `#[Describe]` | From `zero-to-prod/data-model` — package dependency |
| `#[DataModel]` | From `zero-to-prod/data-model` — package dependency |

### 4. `#[MigrationsSource]` on Migrator

The attribute hardcodes Thryds-specific path/namespace. The package ships `Migrator` without it. The app can either:
- Use the constructor directly (already works)
- Put `#[MigrationsSource]` on an app-level subclass and call `::create()`

### 5. Migration table model

`Migration.php` + `MigrationColumns.php` ship with the package — they define the migrator's own tracking table. The `TableName` enum reference becomes a package-internal `BackedEnum`.

## Part C: Extraction Steps

1. Create `zero-to-prod/db` repo with `composer.json`
2. Copy files, namespace `ZeroToProd\Thryds\` → `ZeroToProd\Db\`
3. Strip `#[Infrastructure]`, `#[ClosedSet]`, `#[KeyRegistry]`, `#[KeySource]`, `Domain::*`
4. Refactor `Connection::resolve()` to pluggable resolver
5. Change `#[Table]` to accept `BackedEnum`
6. Create package-internal table name enum for `Migration`
7. Remove `#[MigrationsSource]` from `Migrator` (or make it optional)
8. Verify: grep for `ZeroToProd\Thryds`, `app()`, `#[Infrastructure]` — must be zero
9. Update Thryds: `composer require zero-to-prod/db`, update imports, add `Connection::setResolver()` at boot
10. Delete extracted files from Thryds
11. Run `./run fix:all` in Thryds

## Part D: Multi-Driver Testing

### Test Layers

**Unit tests (no database):**

| Area | What |
|------|------|
| `Driver` enum | Every method, every case — `quote()`, `dsn()`, `typeSql()`, `timezoneCommand()`, etc. |
| `DdlBuilder` | DDL output per driver — string assertions, no DB |
| `DatabaseConfig` | `computeDsn()`, `computePort()`, `castDriver()` per driver |
| `MigrationDiscovery` | Discovers from fixture directory |
| `Persist` resolvers | Pure logic — `RandomIdResolver`, `PasswordHashResolver`, `NowResolver` |

**Integration tests (real database per driver):**

| Area | What |
|------|------|
| `Database` CRUD | `all()`, `one()`, `scalar()`, `execute()`, `insert()`, `transaction()` |
| Query traits | `DbCreate::create()`, `DbRead::one()`/`all()`/`allRows()`, `DbDelete::delete()`, `DbUpdate::update()` |
| Migrator | `migrate()`, `rollback()`, `status()` |
| Transactional DDL | PostgreSQL/SQLite rollback on failure; MySQL partial state |
| DDL execution | Every `DataType` produces valid DDL per driver |

### Test Infrastructure

```yaml
# compose.test.yaml
services:
  mysql:
    image: mysql:8.4
    environment: { MYSQL_ROOT_PASSWORD: test, MYSQL_DATABASE: test_db }
    ports: ["3306:3306"]
    tmpfs: [/var/lib/mysql]

  postgres:
    image: postgres:17
    environment: { POSTGRES_PASSWORD: test, POSTGRES_DB: test_db }
    ports: ["5432:5432"]
    tmpfs: [/var/lib/postgresql/data]
```

SQLite: in-memory (`:memory:`), no service.

### Driver-Parameterized Base

```php
abstract class DatabaseTestCase extends TestCase
{
    protected Database $db;

    abstract protected static function driver(): Driver;

    protected function setUp(): void
    {
        $this->db = new Database(self::configFor(static::driver()));
        Connection::setResolver(fn(string $class) => $this->db);
    }

    private static function configFor(Driver $Driver): DatabaseConfig
    {
        return match ($Driver) {
            Driver::mysql => DatabaseConfig::from([
                DatabaseConfig::driver   => Driver::mysql,
                DatabaseConfig::host     => getenv('TEST_MYSQL_HOST') ?: '127.0.0.1',
                DatabaseConfig::database => getenv('TEST_MYSQL_DATABASE') ?: 'test_db',
                DatabaseConfig::username => getenv('TEST_MYSQL_USERNAME') ?: 'root',
                DatabaseConfig::password => getenv('TEST_MYSQL_PASSWORD') ?: 'test',
            ]),
            Driver::pgsql => DatabaseConfig::from([
                DatabaseConfig::driver   => Driver::pgsql,
                DatabaseConfig::host     => getenv('TEST_PGSQL_HOST') ?: '127.0.0.1',
                DatabaseConfig::database => getenv('TEST_PGSQL_DATABASE') ?: 'test_db',
                DatabaseConfig::username => getenv('TEST_PGSQL_USERNAME') ?: 'postgres',
                DatabaseConfig::password => getenv('TEST_PGSQL_PASSWORD') ?: 'test',
            ]),
            Driver::sqlite => DatabaseConfig::from([
                DatabaseConfig::driver   => Driver::sqlite,
                DatabaseConfig::database => ':memory:',
            ]),
        };
    }
}
```

### Directory Structure

```
tests/
├── Unit/
│   ├── Schema/
│   │   ├── DriverTest.php
│   │   └── DdlBuilderTest.php
│   ├── DatabaseConfigTest.php
│   └── MigrationDiscoveryTest.php
├── Integration/
│   ├── DatabaseTestCase.php
│   ├── Mysql/
│   │   ├── MysqlCrudTest.php
│   │   ├── MysqlMigratorTest.php
│   │   └── MysqlDdlTest.php
│   ├── Pgsql/
│   │   ├── PgsqlCrudTest.php
│   │   ├── PgsqlMigratorTest.php
│   │   └── PgsqlDdlTest.php
│   └── Sqlite/
│       ├── SqliteCrudTest.php
│       ├── SqliteMigratorTest.php
│       └── SqliteDdlTest.php
└── Fixtures/
    ├── TestTable.php
    ├── TestTableName.php
    ├── TestColumns.php
    └── TestMigration.php
```

### CI Matrix

```yaml
strategy:
  matrix:
    driver: [mysql, pgsql, sqlite]
    php: ['8.5']
```

### Coverage Target

- Unit: 100% of `Driver` methods, `DdlBuilder`, `DatabaseConfig`
- Integration: happy path + one error case per trait method per driver
- DDL: every `DataType` case produces executable DDL per driver
