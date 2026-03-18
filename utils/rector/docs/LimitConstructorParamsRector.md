# LimitConstructorParamsRector

Limits constructor parameter count by extracting excess promoted properties into a generated parameter-object DTO suffixed with `Deps`.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when safe) — falls back to `warn` when extraction is unsafe

## Rationale

A constructor with many parameters signals that a class has too many direct dependencies. This makes instantiation fragile, testing verbose, and the dependency graph opaque. Grouping cohesive excess dependencies into a `*Deps` DTO reduces the constructor's surface while keeping the group explicit and nameable.

## What It Detects

`Class_` nodes whose `__construct` has more parameters than `maxParams`. Only fully promoted, typed, body-less constructors are auto-extracted. All other cases receive a TODO comment.

## Transformation

### In `auto` mode

When safe (all params are promoted and typed, constructor body is empty, there are at least 2 excess params):

1. Selects which params to extract using type-prefix grouping (params whose type shares a common word prefix) or co-occurrence grouping (params used exclusively together in the same methods), falling back to the last N params.
2. Generates a `readonly class <ClassName>Deps` file in `dtoOutputDir` (or the source file's directory) containing the extracted params as promoted properties.
3. Rewrites property accesses in all methods from `$this->prop` to `$this->ClassNameDeps->prop`.
4. Replaces the extracted params in the constructor with a single `private readonly ClassNameDeps $ClassNameDeps` param.

### In `warn` mode

Adds `// TODO: Too many constructor parameters (current: N, max: M)` above the constructor.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxParams` | `int` | `5` | Maximum allowed constructor parameters |
| `dtoSuffix` | `string` | `'Deps'` | Suffix appended to the class name to form the DTO class name |
| `dtoOutputDir` | `string` | `''` | Directory to write the generated DTO file; defaults to the source file's directory |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `'TODO: Too many constructor parameters'` | Comment text prefix |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(LimitConstructorParamsRector::class, [
    'maxParams' => 5,
    'dtoSuffix' => 'Deps',
    'mode' => 'auto',
]);
```

## Example

### Before
```php
class OrderProcessor
{
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly NotificationService $NotificationService,
        private readonly AuditLogger $AuditLogger,
    ) {}

    public function process(): void
    {
        $this->OrderRepository->save();
        $this->NotificationService->send();
    }
}
```

### After
```php
class OrderProcessor
{
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly \Fixture\OrderProcessorDeps $OrderProcessorDeps,
    ) {}

    public function process(): void
    {
        $this->OrderRepository->save();
        $this->OrderProcessorDeps->NotificationService->send();
    }
}
```

A new file `OrderProcessorDeps.php` is generated next to the source file.

### Before (unsafe — constructor has a body)
```php
class BodyLogicService
{
    public function __construct(
        private readonly RepoA $RepoA,
        /* ... 5 more repos ... */
    ) {
        $this->validate();
    }
}
```

### After
```php
class BodyLogicService
{
    // TODO: Too many constructor parameters (current: 6, max: 4)
    public function __construct(
        private readonly RepoA $RepoA,
        /* ... 5 more repos ... */
    ) {
        $this->validate();
    }
}
```

## Resolution

When you see the TODO comment:
1. Identify which dependencies naturally cluster together (used by the same methods, or sharing a type-name prefix).
2. Create a `readonly class <ClassName>Deps` file with the clustered dependencies as promoted params.
3. Replace the extracted params in the constructor with a single `private readonly <ClassName>Deps $<ClassName>Deps` param.
4. Update all `$this->extractedProp` references to `$this-><ClassName>Deps->extractedProp`.

## Related Rules

- [`MakeClassReadonlyRector`](MakeClassReadonlyRector.md) — the generated Deps DTO will be a candidate for `readonly`
