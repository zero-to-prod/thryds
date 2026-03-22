# Phase 9: Testing — Multi-Driver Strategy

## Goal

Ensure the extracted package works correctly against MySQL, PostgreSQL, and SQLite with a testing strategy that balances coverage against infrastructure cost.

## Test Layers

### Layer 1: Unit Tests (no database)

Test pure logic that does not require a database connection.

| Test | What it validates |
|------|-------------------|
| `Driver::quote()` | Backticks for MySQL, double quotes for PostgreSQL/SQLite |
| `Driver::dsn()` | DSN format per driver |
| `Driver::timezoneCommand()` | Correct SQL or null for SQLite |
| `Driver::reconnectPatterns()` | Expected error substrings per driver |
| `Driver::autoIncrementSql()` | Correct keyword per driver |
| `Driver::supportsUnsigned()` | true for MySQL, false for others |
| `Driver::typeSql()` | Type mapping per driver x DataType case |
| `Driver::tableOptions()` | MySQL suffix, empty for others |
| `Driver::transactionalDdl()` | false for MySQL, true for others |
| `Driver::enumConstraint()` | CHECK constraint for PostgreSQL, null for others |
| `Driver::defaultPort()` | 3306, 5432, 0 |
| `DatabaseConfig::computeDsn()` | DSN varies by driver |
| `DatabaseConfig::computePort()` | Port defaults by driver |
| `DatabaseConfig::castDriver()` | String-to-enum coercion |
| `DdlBuilder::createTableSql()` | Full DDL output per driver |
| `DdlBuilder::columnDdl()` | Column DDL per driver |
| `DdlBuilder::addColumnSql()` | ALTER TABLE per driver |
| `DdlBuilder::dropColumnSql()` | ALTER TABLE per driver, RuntimeException for SQLite |
| `DdlBuilder::reflectPrimaryKey()` | Reflection logic (driver-agnostic) |
| `DdlBuilder::reflectColumns()` | Reflection logic (driver-agnostic) |
| `DdlBuilder::reflectForeignKeys()` | FK clause per driver |
| `Persist` resolvers | `RandomIdResolver`, `PasswordHashResolver`, `NowResolver` — pure logic |
| `MigrationDiscovery` | Discovers files and attributes from fixture directory |
| `MigrationStatusRow` | DTO construction via DataModel |
| `Sql` constants | Compile-time — existence and values |

### Layer 2: Integration Tests (real database per driver)

Test actual SQL execution against each database backend.

| Test | What it validates |
|------|-------------------|
| `Database::execute()` | CREATE TABLE, INSERT, UPDATE, DELETE work per driver |
| `Database::all()` | SELECT returns rows per driver |
| `Database::one()` | SELECT returns single row per driver |
| `Database::scalar()` | SELECT scalar value per driver |
| `Database::transaction()` | Commit and rollback per driver |
| `Database::insert()` | lastInsertId per driver |
| Reconnect behavior | MySQL/PostgreSQL reconnect; SQLite empty patterns |
| Timezone setting | MySQL/PostgreSQL execute; SQLite skips |
| `Connection::resolve()` | Resolver returns correct Database per table |
| `DbCreate::create()` | INSERT via trait per driver |
| `DbRead::one()` | SELECT via trait per driver |
| `DbRead::all()` | SELECT with WHERE per driver |
| `DbRead::allRows()` | SELECT with ORDER BY, LIMIT, OFFSET per driver |
| `DbRead::oneRow()` | SELECT with ORDER BY and LIMIT 1 per driver |
| `DbDelete::delete()` | DELETE via trait per driver |
| `DbUpdate::update()` | UPDATE via trait per driver |
| `Migrator::migrate()` | Apply migrations per driver |
| `Migrator::rollback()` | Rollback migrations per driver |
| `Migrator::status()` | Status reporting per driver (returns `MigrationStatusRow` DTOs) |
| `Migrator::create()` | Factory reads `#[MigrationsSource]` per driver |
| Transactional DDL | PostgreSQL/SQLite rollback on failure; MySQL partial state |

### Layer 3: DDL Verification Tests

Parse generated DDL and execute it against each database to verify syntax.

| Test | What it validates |
|------|-------------------|
| CREATE TABLE with all column types | Every `DataType` case produces valid DDL per driver |
| CREATE TABLE with PRIMARY KEY | Single and composite keys per driver |
| CREATE TABLE with INDEX | Standard and UNIQUE indexes per driver |
| CREATE TABLE with FOREIGN KEY | Constraint syntax per driver |
| ALTER TABLE ADD COLUMN | Add column per driver |
| ALTER TABLE DROP COLUMN | Works on MySQL/PostgreSQL, throws on SQLite |
| ENUM emulation | MySQL uses ENUM, PostgreSQL uses CHECK, SQLite uses TEXT |
| `#[RawSql]` migration | Executes consumer SQL per driver |

## Test Infrastructure

### Docker Compose for CI

```yaml
# compose.test.yaml
services:
  mysql:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: test
      MYSQL_DATABASE: test_db
    ports:
      - "3306:3306"
    tmpfs:
      - /var/lib/mysql

  postgres:
    image: postgres:17
    environment:
      POSTGRES_PASSWORD: test
      POSTGRES_DB: test_db
    ports:
      - "5432:5432"
    tmpfs:
      - /var/lib/postgresql/data
```

SQLite requires no service — uses in-memory (`:memory:`) or a temp file.

### PHPUnit Configuration

```xml
<phpunit>
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration-mysql">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="integration-pgsql">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="integration-sqlite">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Driver-Parameterized Integration Tests

A base test case provides database setup and `Connection::setResolver()`:

```php
abstract class DatabaseTestCase extends TestCase
{
    protected Database $db;
    protected Driver $driver;

    abstract protected static function driver(): Driver;

    protected function setUp(): void
    {
        $this->driver = static::driver();
        $config = self::configFor($this->driver);
        $this->db = new Database($config);

        // Wire the Connection resolver for this test
        Connection::setResolver(fn(string $class) => $this->db);
    }

    protected function tearDown(): void
    {
        // Drop all test tables
    }

    private static function configFor(Driver $Driver): DatabaseConfig
    {
        return match ($Driver) {
            Driver::mysql => DatabaseConfig::from([
                DatabaseConfig::driver   => Driver::mysql,
                DatabaseConfig::host     => getenv('TEST_MYSQL_HOST') ?: '127.0.0.1',
                DatabaseConfig::port     => (int) (getenv('TEST_MYSQL_PORT') ?: 3306),
                DatabaseConfig::database => getenv('TEST_MYSQL_DATABASE') ?: 'test_db',
                DatabaseConfig::username => getenv('TEST_MYSQL_USERNAME') ?: 'root',
                DatabaseConfig::password => getenv('TEST_MYSQL_PASSWORD') ?: 'test',
            ]),
            Driver::pgsql => DatabaseConfig::from([
                DatabaseConfig::driver   => Driver::pgsql,
                DatabaseConfig::host     => getenv('TEST_PGSQL_HOST') ?: '127.0.0.1',
                DatabaseConfig::port     => (int) (getenv('TEST_PGSQL_PORT') ?: 5432),
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

Concrete test classes per driver:

```php
final class MysqlDatabaseTest extends DatabaseTestCase
{
    protected static function driver(): Driver { return Driver::mysql; }
}

final class PgsqlDatabaseTest extends DatabaseTestCase
{
    protected static function driver(): Driver { return Driver::pgsql; }
}

final class SqliteDatabaseTest extends DatabaseTestCase
{
    protected static function driver(): Driver { return Driver::sqlite; }
}
```

## Test Directory Structure

```
tests/
├── Unit/
│   ├── Schema/
│   │   ├── DriverTest.php          # All Driver enum method tests
│   │   ├── DdlBuilderTest.php      # DDL generation per driver (no DB)
│   │   └── DataTypeTest.php        # DataType enum completeness
│   ├── Queries/
│   │   └── PersistResolverTest.php  # Resolver unit tests
│   ├── DatabaseConfigTest.php       # DSN computation, port, driver coercion
│   ├── MigrationDiscoveryTest.php   # Discovery from fixture directory
│   └── MigrationStatusRowTest.php   # DTO construction
├── Integration/
│   ├── DatabaseTestCase.php         # Abstract base with driver setup + Connection wiring
│   ├── Mysql/
│   │   ├── MysqlDatabaseTest.php
│   │   ├── MysqlQueryTraitTest.php
│   │   ├── MysqlMigratorTest.php
│   │   └── MysqlDdlTest.php
│   ├── Pgsql/
│   │   ├── PgsqlDatabaseTest.php
│   │   ├── PgsqlQueryTraitTest.php
│   │   ├── PgsqlMigratorTest.php
│   │   └── PgsqlDdlTest.php
│   └── Sqlite/
│       ├── SqliteDatabaseTest.php
│       ├── SqliteQueryTraitTest.php
│       ├── SqliteMigratorTest.php
│       └── SqliteDdlTest.php
└── Fixtures/
    ├── TestTable.php                # Minimal table model for testing
    ├── TestTableName.php            # BackedEnum with test table name
    ├── TestColumns.php              # Column trait for TestTable
    └── TestMigration.php            # Minimal migration for testing
```

## CI Matrix

```yaml
# GitHub Actions
strategy:
  matrix:
    driver: [mysql, pgsql, sqlite]
    php: ['8.5']
```

- Unit tests run on all matrix entries (no DB needed)
- Integration tests run per driver (services started conditionally)
- SQLite tests need no external service

## Test Fixtures

### Minimal Test Table

```php
#[Connection(database: Database::class)]
#[Table(TableName: TestTableName::test_items)]
#[PrimaryKey(columns: ['id'])]
class TestTable
{
    use HasTableName;
    use DataModel;
    use TestColumns;
}
```

```php
trait TestColumns
{
    public const string id = 'id';
    #[Column(DataType: DataType::VARCHAR, length: 26, ...)]
    #[PrimaryKey]
    public string $id;

    public const string name = 'name';
    #[Column(DataType: DataType::VARCHAR, length: 255, ...)]
    public string $name;

    public const string score = 'score';
    #[Column(DataType: DataType::INT, unsigned: true, ...)]
    public int $score;

    public const string created_at = 'created_at';
    #[Column(DataType: DataType::DATETIME, default: Column::CURRENT_TIMESTAMP, ...)]
    public string $created_at;
}
```

This fixture exercises: VARCHAR, INT, DATETIME, UNSIGNED, DEFAULT, PRIMARY KEY — enough to validate cross-driver DDL and queries.

## Implementation Steps

### Step 1: Create test directory structure

### Step 2: Write unit tests for `Driver` enum

- One test per method per driver case
- Assert exact string output for `quote()`, `dsn()`, `typeSql()`, etc.

### Step 3: Write unit tests for `DdlBuilder` per driver

- Pass each `Driver` case, assert generated DDL contains driver-appropriate syntax

### Step 4: Write unit tests for `DatabaseConfig`

- Test `computeDsn()`, `computePort()`, `castDriver()` per driver

### Step 5: Create test fixtures

- `TestTable`, `TestTableName`, `TestColumns`, `TestMigration`

### Step 6: Write `DatabaseTestCase` base class

- Include `Connection::setResolver()` wiring

### Step 7: Write integration tests per driver

- MySQL, PostgreSQL, SQLite suites
- Each suite: connection, CRUD via traits, transactions, migrator

### Step 8: Set up Docker Compose for CI

### Step 9: Set up GitHub Actions matrix

### Step 10: Run full suite

## Coverage Target

- Unit tests: 100% of `Driver` methods, `DdlBuilder` public methods, `DatabaseConfig`, `MigrationDiscovery`
- Integration tests: happy path + one error case per trait method per driver
- DDL tests: every `DataType` case produces executable DDL per driver
