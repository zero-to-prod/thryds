# LimitConstructorParamsRector

## Tool

Rector (custom rule)

## What it does

When a constructor exceeds a configurable parameter count, automatically groups related
parameters into a typed parameter object (DTO). When grouping cannot be determined
safely, adds a TODO comment instead.

## Why it matters

Constructor parameter count is a direct proxy for how many things a class depends on.
An agent reasoning about a method in a class with 8 constructor dependencies must
consider all 8 as potential actors — even if the method only uses 2. Fewer dependencies
mean smaller blast radius.

## Refactoring strategy

### Auto-fix: extract promoted properties into a parameter object

When all excess parameters are constructor-promoted properties with declared types,
the rule can extract a subset into a new readonly class.

**Grouping heuristic**: Parameters that share a type-name prefix or are used together
in the same methods are grouped. When no natural grouping is detected, the last
N parameters (those exceeding the limit) are grouped together.

```php
// before
class OrderProcessor {
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly NotificationService $NotificationService,
        private readonly AuditLogger $AuditLogger,
    ) {}

    public function process(Order $Order): void {
        $this->PaymentGateway->charge($Order);
        $this->ShippingService->ship($Order);
        $this->NotificationService->send($Order);
    }
}

// after — NotificationService and AuditLogger grouped (neither used in core flow)
// New file: OrderProcessorDeps.php
readonly class OrderProcessorDeps {
    public function __construct(
        public NotificationService $NotificationService,
        public AuditLogger $AuditLogger,
    ) {}
}

// Modified:
class OrderProcessor {
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly OrderProcessorDeps $OrderProcessorDeps,
    ) {}

    public function process(Order $Order): void {
        $this->PaymentGateway->charge($Order);
        $this->ShippingService->ship($Order);
        $this->OrderProcessorDeps->NotificationService->send($Order);
    }
}
```

### When extraction is not safe (fall back to TODO)

- Parameters are not promoted properties (plain assignments in the body — harder to
  trace usage).
- Parameters have no declared type (the DTO would have untyped properties).
- The constructor has logic beyond simple assignment (validation, transformation).
- Fewer than 2 parameters exceed the limit (extracting a single-property DTO adds
  noise rather than reducing it).

```php
// TODO: Too many constructor parameters (current: 7, max: 5)
public function __construct(
    private $untyped,
    // ...
) {
    $this->computed = transform($untyped);
}
```

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `maxParams` | `int` | `5` | Maximum constructor parameters before triggering |
| `dtoSuffix` | `string` | `'Deps'` | Suffix for generated parameter object class name |
| `dtoOutputDir` | `string` | `''` | Directory for generated DTO files. Empty = same directory as the source class. |
| `todoMessage` | `string` | `'TODO: Too many constructor parameters'` | Comment prefix when auto-fix is not safe (count info is appended) |

### Example rector.php

```php
use Utils\Rector\Rector\LimitConstructorParamsRector;

$rectorConfig->ruleWithConfiguration(LimitConstructorParamsRector::class, [
    'maxParams' => 5,
    'dtoSuffix' => 'Deps',
]);
```

## Implementation notes

- **Node types**: `ClassMethod`
- **Detection**: Check `$node->name->toString() === '__construct'`, count `$node->params`.
- **Promoted property check**: Verify each param has `$param->flags !== 0` (visibility
  flag set) and `$param->type !== null`.
- **Usage analysis**: For each promoted property, scan all methods in the class for
  `$this->propertyName` usage. Build a usage map: `property → [method1, method2, ...]`.
  Properties that co-occur in the same methods are candidates for grouping.
- **DTO generation**: Create a new `Class_` node marked `readonly` with `public`
  promoted constructor params. Write to a new file in `dtoOutputDir` or alongside the
  source file.
- **Reference rewriting**: In the original class, replace `$this->extractedProp` with
  `$this->dtoName->extractedProp` using `traverseNodesWithCallable`.
- **Constructor rewriting**: Remove extracted params from the constructor. Add a new
  param for the DTO with appropriate type.
- **Import handling**: Add `use` statement for the new DTO class.
- Implements `ConfigurableRectorInterface`
