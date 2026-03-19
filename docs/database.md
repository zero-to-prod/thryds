# Database Abstraction

Vanilla PDO wrapper for Thryds. No ORM, no query builder DSL — SQL is written directly and the abstraction handles only the repetitive mechanics: parameter binding, fetch mode, transactions, and worker-mode reconnection.

---

## File Map

| Artifact | Path |
|---|---|
| `Database` class | `src/Database.php` |
| `DatabaseConfig` class | `src/DatabaseConfig.php` |
| Repository convention | `src/Repositories/UserRepository.php` (example) |
| `.env.example` additions | `.env.example` |
| `compose.yaml` db service | `compose.yaml` |

---

## Design Constraints

- **Worker mode** (one connection per worker process, constructed once in `App::boot()` and reused across all requests — like `Blade` and `Router`): The `Database` object must not be reconstructed per-request.
- **No service container** (`Database` is wired explicitly in `App::boot()` and injected into controllers as a constructor argument — no dependency injection container exists in this project).
- **Least invasive**: The class does not generate SQL, wrap models, or define relationships. It executes what you give it.

---

## The `Database` Class

> **Note on reconnect:** `run()` needs access to the original `DatabaseConfig` to reconnect on gone-away. Store it as a second property — `private readonly DatabaseConfig $Config` — alongside `$pdo`. The `readonly` modifier applies per-property, not to the class as a whole.

```php
readonly class Database
{
    private PDO $pdo;
    private DatabaseConfig $Config;

    public function __construct(DatabaseConfig $Config)
    {
        $this->Config = $Config;
        $this->pdo = self::connect($Config);
    }

    // SELECT — returns all rows as associative arrays
    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SELECT — returns one row or null
    public function one(string $sql, array $params = []): array|null
    {
        $stmt = $this->run($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    // SELECT — returns a single scalar value or null
    public function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->run($sql, $params);
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }

    // INSERT / UPDATE / DELETE — returns affected row count
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        return $stmt->rowCount();
    }

    // INSERT — returns last insert ID
    public function insert(string $sql, array $params = []): string
    {
        $this->run($sql, $params);
        return $this->pdo->lastInsertId();
    }

    // Wrap a block in a transaction; re-throws on failure
    public function transaction(Closure $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function run(string $sql, array $params): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (self::isGoneAway($e)) {
                $this->pdo = self::connect($this->Config);
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
            throw $e;
        }
    }

    private static function connect(DatabaseConfig $Config): PDO
    {
        $pdo = new PDO(
            dsn: $Config->dsn,
            username: $Config->username,
            password: $Config->password,
            options: [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false, // see Worker Mode note below
            ],
        );
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    }

    private static function isGoneAway(PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'server has gone away')
            || str_contains($e->getMessage(), 'Lost connection');
    }
}
```

---

## Configuration

Add `DatabaseConfig` alongside `Config`:

```php
readonly class DatabaseConfig
{
    public string $dsn;

    public function __construct(
        public string $host,
        public int    $port,
        public string $database,
        public string $username,
        public string $password,
    ) {
        $this->dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }

    public static function fromEnv(): self
    {
        return new self(
            host:     (string) getenv('DB_HOST'),
            port:     (int)    (getenv('DB_PORT') ?: 3306),
            database: (string) getenv('DB_DATABASE'),
            username: (string) getenv('DB_USERNAME'),
            password: (string) getenv('DB_PASSWORD'),
        );
    }
}
```

`.env.example` additions:
```
DB_HOST=db
DB_PORT=3306
DB_DATABASE=thryds
DB_USERNAME=thryds
DB_PASSWORD=secret
```

Start the database locally:

```
docker compose -f compose.yaml -f compose.development.yaml up -d db
```

Confirm it is healthy before testing:

```
docker compose exec db mysql -u thryds -psecret thryds -e "SELECT 1"
```

---

## Wiring into `App::boot()`

```php
public static function boot(string $base_dir, ?Config $Config = null): self
{
    // ... existing boot code ...

    $Database = new Database(DatabaseConfig::fromEnv());

    return new self($Config, $Blade, $Router, $Database);
}
```

`App` gains a `public Database $Database` constructor argument.

---

## Wiring Repositories

Each repository is added as a `public` constructor argument on `App`, instantiated in `App::boot()` immediately after `$Database`, and passed into `new self(...)`:

```php
$Database = new Database(DatabaseConfig::fromEnv());
$Users    = new UserRepository($Database);

return new self($Config, $Blade, $Router, $Database, $Users);
```

Repositories live in `src/Repositories/`. They are then injected into controllers as constructor arguments.

---

## Verify

After wiring, run:

```
./run check:all
```

Expect: PHPStan passes, all tests green. If the `db` service is not running, a `PDOException: Connection refused` will appear in `logs/php/error.log`.

---

## Worker Mode: Connection Lifecycle

FrankenPHP workers are long-lived processes. The PDO connection is created once per worker at boot and reused for every request. Two failure modes require handling:

| Scenario | Cause | Mitigation |
|---|---|---|
| **Server gone away** | MySQL closes idle connections after `wait_timeout` (default 8h) | Reconnect-on-failure in `run()` |
| **Mid-request disconnect** | Network blip during query | PDOException propagates; worker restarts |
| **Transaction leak** | Exception exits a transaction without rollback | `transaction()` always calls `rollBack()` on throw |

`PDO::ATTR_PERSISTENT => false` is intentional. Persistent connections in long-lived workers can produce duplicate connection state rather than reuse, and MySQL's connection count becomes unpredictable. One explicit connection per worker process is cleaner.

---

## Usage Patterns

### Plain queries

```php
// All rows
$posts = $db->all('SELECT id, title, created_at FROM posts WHERE user_id = :user_id', [
    'user_id' => $userId,
]);

// Single row
$user = $db->one('SELECT id, email FROM users WHERE id = :id', ['id' => $id]);
if ($user === null) {
    // not found
}

// Scalar
$count = $db->scalar('SELECT COUNT(*) FROM posts WHERE published = 1');
```

### Writes

```php
// Insert, capture new ID
$id = $db->insert(
    'INSERT INTO posts (user_id, title, body, created_at) VALUES (:user_id, :title, :body, NOW())',
    ['user_id' => $userId, 'title' => $title, 'body' => $body]
);

// Update / delete — check affected rows
$affected = $db->execute(
    'UPDATE users SET email = :email WHERE id = :id',
    ['email' => $email, 'id' => $id]
);
```

### Transactions

```php
$db->transaction(function (Database $db) use ($userId, $items): void {
    $orderId = $db->insert(
        'INSERT INTO orders (user_id, created_at) VALUES (:user_id, NOW())',
        ['user_id' => $userId]
    );

    foreach ($items as $item) {
        $db->execute(
            'INSERT INTO order_items (order_id, product_id, qty) VALUES (:order_id, :product_id, :qty)',
            ['order_id' => $orderId, 'product_id' => $item['id'], 'qty' => $item['qty']]
        );
    }
});
```

### Repositories

Group queries by entity. A repository is a plain class — no base class, no interface required:

```php
readonly class UserRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): array|null
    {
        return $this->db->one(
            'SELECT id, email, name, created_at FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): array|null
    {
        return $this->db->one(
            'SELECT id, email, name FROM users WHERE email = :email',
            ['email' => $email]
        );
    }

    public function create(string $email, string $name): string
    {
        return $this->db->insert(
            'INSERT INTO users (email, name, created_at) VALUES (:email, :name, NOW())',
            ['email' => $email, 'name' => $name]
        );
    }
}
```

---

## What This Abstraction Does Not Do

| Omitted | Why |
|---|---|
| Query builder / fluent interface | SQL is readable enough; a builder adds syntax to learn with no readability gain |
| Model classes / Active Record | Returns plain `array` — no magic properties, no hidden lazy loads |
| Schema migrations | Migrations are a separate concern; use a standalone migration tool |
| Connection pooling | One connection per worker; pooling adds state complexity with no throughput gain at this scale |
| Automatic retry loops | Reconnecting once on gone-away is safe; more retries mask real failures |

---

## Docker Compose Addition

Add a `db` service to `compose.yaml`:

```yaml
services:
  db:
    image: mysql:9.2
    environment:
      MYSQL_DATABASE: thryds
      MYSQL_USER: thryds
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"

volumes:
  db_data:
```

The `web` service should declare `depends_on: [db]`.
