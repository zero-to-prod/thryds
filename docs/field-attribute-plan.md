# Plan: Unified `#[Field]` Attribute

## Problem

The form system splits field definition across multiple disconnected locations:

| Concern | Current location | Attribute |
|---|---|---|
| Schema constraints | `UserColumns` trait (property) | `#[Column]` |
| Input rendering | `UserColumns` trait (property) | `#[Input]` |
| Validation rules | `RegisterRequest` (class-level) | `#[Validates]` |
| Cross-field match | `RegisterRequest` (property) | `#[Matches]` |
| Error structure | `RegisterViewModel` (class-level) | `#[HasValidationErrors]` |

**Consequences of this split:**

1. Add a field to `UserColumns` with `#[Input]` but forget `#[Validates]` on the request — no validation, no error at build time.
2. Rename a column constant — `#[Validates]` references break silently (string match in class-level attribute vs. property name).
3. Different request classes that share fields (register vs. profile-update) must duplicate `#[Validates]` declarations with potentially different rules — nothing ties them back to the column.
4. `InputField::reflect()` must cross-reference class-level `#[Validates]` with property-level `#[Input]` to derive the `required` flag — a runtime join across two attribute locations.
5. `Validated` action holds `$request`, `$view_model`, `$controller` but says nothing about what gets validated or how — the rules are invisible at the route definition site.

## Goal

A single property-level attribute — `#[Field]` — that declares:

1. **Data source** — which table and column this field is backed by (optional).
2. **Input rendering** — HTML input type, label, display order.
3. **Validation rules** — explicit rules, merged with rules derived from the column when present.

Different request classes reference the same source column with context-specific rules. Changing a field means changing one attribute on one property.

## Design

### The `Field` attribute

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
#[HopWeight(0)]
readonly class Field
{
    /** @var list<array{Rule, int|string|null}> */
    public array $normalizedRules;

    /**
     * @param class-string|null $table    Source table class (null = no backing column)
     * @param string|null       $column   Column constant from that table
     * @param list<Rule|array{Rule, int|string|null}> $rules Validation rules beyond what the column implies
     */
    public function __construct(
        public ?string $table,
        public ?string $column,
        public InputType $input,
        public string $label,
        public int $order,
        public array $rules = [],
    ) {
        $this->normalizedRules = array_values(array_map(
            static fn(Rule|array $rule): array => $rule instanceof Rule
                ? [$rule, null]
                : $rule,
            $rules,
        ));
    }
}
```

### Column-derived rules

When `$table` and `$column` are provided, the framework resolves the `#[Column]` attribute from the table class at runtime and derives baseline rules:

| Column metadata | Derived rule | Derived HTML attr |
|---|---|---|
| `nullable: false` | `Rule::required` | `required` |
| `length: N` | `[Rule::max, N]` | `maxlength="N"` |

Explicit `rules` on `#[Field]` are **additive** — they merge with (never replace) column-derived rules. If a conflict arises (e.g., explicit `Rule::required` on a `nullable: false` column), the explicit rule is deduplicated.

### Before / After: `RegisterRequest`

**Before** (current):
```php
#[Validates(User::name, Rule::required)]
#[Validates(User::handle, Rule::required)]
#[Validates(User::email, Rule::required, Rule::email)]
#[Validates(User::password, Rule::required, [Rule::min, 8])]
#[Validates(self::password_confirmation, Rule::required)]
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;  // brings #[Input] on properties, but not rules

    #[Input(InputType::password, 'Confirm Password', order: 5)]
    #[Matches(User::password)]
    public string $password_confirmation;
}
```

**After**:
```php
readonly class RegisterRequest
{
    use DataModel;

    #[Field(User::class, User::name, InputType::text, 'Name', order: 1)]
    public ?string $name;

    #[Field(User::class, User::handle, InputType::text, 'Handle', order: 2)]
    public ?string $handle;

    #[Field(User::class, User::email, InputType::email, 'Email', order: 3, rules: [Rule::email])]
    public ?string $email;

    #[Field(User::class, User::password, InputType::password, 'Password', order: 4, rules: [[Rule::min, 8]])]
    public ?string $password;

    #[Field(null, null, InputType::password, 'Confirm Password', order: 5, rules: [Rule::required])]
    #[Matches(User::password)]
    public string $password_confirmation;
}
```

- No `#[Validates]` at class level.
- No `UserColumns` trait — the request declares its own properties with `#[Field]` pointing to the source column.
- `Rule::required` and `Rule::max` are derived from the column — only `Rule::email` and `[Rule::min, 8]` are explicit.
- `password_confirmation` has no backing column (`null, null`), so `Rule::required` must be explicit.

### 1-to-many: same column, different rules

```php
// Registration: all fields required (from column nullable:false), email format enforced
readonly class RegisterRequest
{
    use DataModel;

    #[Field(User::class, User::name, InputType::text, 'Name', order: 1)]
    public ?string $name;

    #[Field(User::class, User::email, InputType::email, 'Email', order: 2, rules: [Rule::email])]
    public ?string $email;

    #[Field(User::class, User::password, InputType::password, 'Password', order: 3, rules: [[Rule::min, 8]])]
    public ?string $password;
    // ...
}

// Profile update: email optional (override column-derived required), no password
readonly class ProfileUpdateRequest
{
    use DataModel;

    #[Field(User::class, User::name, InputType::text, 'Display Name', order: 1)]
    public ?string $name;

    #[Field(User::class, User::email, InputType::email, 'Email', order: 2, rules: [Rule::email], optional: true)]
    public ?string $email;
}
```

The `optional: true` flag suppresses the column-derived `Rule::required`, giving per-request control without duplicating the column definition.

### Before / After: `UserColumns` trait

**Before**: carries `#[Input]` on properties, used by both `User`, `RegisterRequest`, and `RegisterViewModel`.

**After**: `#[Input]` is removed from `UserColumns`. The trait remains as the schema definition with `#[Column]`, `#[PrimaryKey]`, `#[Describe]`, `#[StubValue]`. It is still used by `User` (table model) and `RegisterViewModel` (for repopulated values). Request classes no longer use the trait — they declare their own properties with `#[Field]`.

### Before / After: `InputField::reflect()`

**Before**: cross-references class-level `#[Validates]` with property-level `#[Input]` to build field metadata.

**After**: reads `#[Field]` from properties directly. Everything is in one place.

```php
public static function reflect(string $class): array
{
    $fields = [];
    $ReflectionClass = new ReflectionClass($class);

    foreach ($ReflectionClass->getProperties() as $property) {
        $fieldAttrs = $property->getAttributes(Field::class);
        if ($fieldAttrs === []) {
            continue;
        }

        $Field = $fieldAttrs[0]->newInstance();
        $resolvedRules = self::resolveRules($Field);

        $fields[] = new self(
            name: $property->getName(),
            InputType: $Field->input,
            label: $Field->label,
            required: self::hasRequired($resolvedRules),
            order: $Field->order,
        );
    }

    usort($fields, static fn(self $a, self $b): int => $a->order <=> $b->order);
    return $fields;
}
```

### Before / After: `Validator::validate()`

**Before**: reads class-level `#[Validates]`, property-level `#[ValidateWith]`, property-level `#[Matches]`.

**After**: reads property-level `#[Field]` (with column-derived rules merged), property-level `#[ValidateWith]`, property-level `#[Matches]`.

```php
public static function validate(object $model): array
{
    $errors = [];
    $ReflectionClass = new ReflectionClass($model);

    foreach ($ReflectionClass->getProperties() as $property) {
        $name = $property->getName();
        $fieldAttrs = $property->getAttributes(Field::class);
        $validateWithAttrs = $property->getAttributes(ValidateWith::class);
        $matchesAttrs = $property->getAttributes(Matches::class);

        if ($fieldAttrs === [] && $validateWithAttrs === [] && $matchesAttrs === []) {
            continue;
        }

        $value = $property->getValue($model);

        // Field-driven validation (column-derived + explicit rules)
        if ($fieldAttrs !== []) {
            $Field = $fieldAttrs[0]->newInstance();
            $resolvedRules = FieldRules::resolve($Field);

            foreach ($resolvedRules as [$rule, $config]) {
                if ($rule->passes($value, $config)) {
                    continue;
                }
                $errors[self::errorKey($name)] = $rule->message($name, $config);
                break 2;
            }
        }

        // ValidateWith (unchanged)
        // Matches (unchanged)
    }

    return $errors;
}
```

### Before / After: `Validated` action and `RouteRegistrar`

**Before**: `Validated` holds `$controller`, `$request`, `$view_model`. `RouteRegistrar::withValidation()` creates request from body, validates, re-renders via Form action on error.

**After**: No change to `Validated` or `RouteRegistrar`. The validation contract is the same — `Validator::validate($requestObject)` — only the validator's source of rules changes from class-level `#[Validates]` to property-level `#[Field]`.

### Before / After: `RegisterViewModel`

**Before**: `#[HasValidationErrors(RegisterRequest::class)]`, uses `UserColumns` trait.

**After**: Still uses `UserColumns` trait (for repopulated values). `#[HasValidationErrors]` can remain or be replaced by reading `#[Field]` attributes from the request class to know which error keys exist. No functional change required.

### Column-derived rule resolution

New infrastructure class `FieldRules` encapsulates rule derivation:

```php
#[Infrastructure]
final readonly class FieldRules
{
    /**
     * Merge column-derived rules with explicit rules from #[Field].
     *
     * @return list<array{Rule, int|string|null}>
     */
    public static function resolve(Field $Field): array
    {
        $rules = [];

        if ($Field->table !== null && $Field->column !== null) {
            $Column = self::resolveColumn($Field->table, $Field->column);

            if ($Column !== null) {
                if (!$Column->nullable && !$Field->optional) {
                    $rules[] = [Rule::required, null];
                }
                if ($Column->length !== null) {
                    $rules[] = [Rule::max, $Column->length];
                }
            }
        }

        // Merge explicit rules, deduplicating
        foreach ($Field->normalizedRules as $rule) {
            if (!self::contains($rules, $rule[0])) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }
}
```

## Implementation Steps

### Step 1: Create `#[Field]` attribute

- File: `src/Attributes/Field.php`
- Property-level attribute with: `$table`, `$column`, `$input`, `$label`, `$order`, `$rules`, `$optional`
- Normalizes rules in constructor (same pattern as current `Validates`)
- Add `#[HopWeight(0)]` for attribute graph

### Step 2: Create `FieldRules` resolver

- File: `src/Validation/FieldRules.php`
- `resolve(Field): list<array{Rule, int|string|null}>` — merges column-derived + explicit rules
- `resolveColumn(string $table, string $column): ?Column` — reflects `#[Column]` from table class property
- Caches resolved columns per class+property

### Step 3: Update `Validator::validate()`

- File: `src/Validation/Validator.php`
- Read `#[Field]` from properties instead of `#[Validates]` from class
- Call `FieldRules::resolve()` for each `#[Field]` property
- Keep `#[ValidateWith]` and `#[Matches]` handling unchanged

### Step 4: Update `InputField::reflect()`

- File: `src/Requests/InputField.php`
- Read `#[Field]` from properties instead of `#[Input]` + class-level `#[Validates]` cross-reference
- Derive `required` from resolved rules instead of scanning class-level attributes
- Derive `InputType`, `label`, `order` from `#[Field]`

### Step 5: Migrate `RegisterRequest`

- File: `src/Requests/RegisterRequest.php`
- Remove all class-level `#[Validates]` attributes
- Remove `use UserColumns` trait
- Declare properties with `#[Field]` pointing to `User::class` + column constants
- Keep `#[Matches]` on `password_confirmation`

### Step 6: Remove `#[Input]` from `UserColumns`

- File: `src/Tables/UserColumns.php`
- Remove all `#[Input]` attributes from column properties
- `UserColumns` becomes purely a schema definition trait
- `User` model and `RegisterViewModel` continue to use the trait (schema + repopulated values)

### Step 7: Update `register.blade.php`

- No change expected — template already iterates `$fields` (list of `InputField`) which is built by `InputField::reflect()`. The reflect method changes internally but the output shape is identical.

### Step 8: Add `optional` flag support

- Add `public bool $optional = false` to `Field` constructor
- When `optional: true`, suppress column-derived `Rule::required` even if `nullable: false`
- Enables per-request override of column constraints

### Step 9: Deprecate / remove `#[Validates]` and `#[Input]`

- If no other consumers exist, remove `src/Attributes/Validates.php` and update `src/Attributes/Input.php`
- If other code reads `#[Input]` (e.g., Blade template preload, attribute graph), migrate those reads to `#[Field]`
- Update `thryds.yaml` manifest if these attributes are declared there

### Step 10: Add Rector rule (enforcement)

- Rule: every property with `#[Field]` that has `table` + `column` must reference a valid column (the column constant must exist on the table class and carry `#[Column]`)
- Rule: request classes used in `Validated` actions must have at least one `#[Field]` property
- Rule: warn if a request class still uses class-level `#[Validates]` (migration lint)

### Step 11: Run `./run fix:all`

- `sync:manifest` — update scaffolding
- `fix:style` — code style
- `fix:rector` — apply rules
- `check:all` — full verification

## Files Changed

| File | Action |
|---|---|
| `src/Attributes/Field.php` | **Create** — new unified attribute |
| `src/Validation/FieldRules.php` | **Create** — column-derived rule resolution |
| `src/Validation/Validator.php` | **Modify** — read `#[Field]` instead of `#[Validates]` |
| `src/Requests/InputField.php` | **Modify** — read `#[Field]` instead of `#[Input]` + `#[Validates]` |
| `src/Requests/RegisterRequest.php` | **Modify** — replace traits + `#[Validates]` with `#[Field]` properties |
| `src/Tables/UserColumns.php` | **Modify** — remove `#[Input]` attributes |
| `src/Attributes/Validates.php` | **Remove** (after migration) |
| `src/Attributes/Input.php` | **Remove** (after migration) |
| `src/Attributes/HasValidationErrors.php` | **Evaluate** — may be removable if error keys derive from `#[Field]` |
| `thryds.yaml` | **Update** if attributes are declared in manifest |
| `attribute-graph.yaml` | **Update** if attribute references change |

## Risks and Mitigations

| Risk | Mitigation |
|---|---|
| `UserColumns` trait removal from requests breaks `DataModel::from()` population | Request properties declared directly with same names — `DataModel` populates by property name, not trait origin |
| Column resolution adds runtime reflection cost | Cache resolved `#[Column]` per class+property (same pattern as `Route::on()`) |
| `#[Input]` is read by other systems (Blade preload, attribute graph) | Audit all `Input::class` references before removing; migrate to `Field::class` |
| `RegisterViewModel` still uses `UserColumns` for repopulated values | No change — ViewModel keeps the trait; only request classes migrate |
| `optional` flag creates implicit rule suppression | Document: `optional` only suppresses column-derived `required`, not explicit rules |
