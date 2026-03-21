# Attribute-Driven Validation

## Goal

Replace inline controller validation with declarative attributes on request data models. Validation rules live where the data is defined, not where it is consumed.

## Organizing Principles Applied

- **Constants name things** — field names are already constants on data models
- **Enumerations define sets** — `Rule` enum defines the set of built-in validation rules
- **PHP Attributes define properties** — `#[Validate]` and `#[ValidateWith]` declare validation constraints on properties

## Design

### Rule Enum

Defines the closed set of built-in validation rules. Each case carries its own validation logic and error message template.

```php
// src/Validation/Rule.php
enum Rule: string
{
    case required = 'required';
    case email    = 'email';
    case min      = 'min';
    case max      = 'max';
    case matches  = 'matches';

    public function passes(mixed $value, int|string|null $config, object $context): bool
    {
        return match ($this) {
            self::required => (string) $value !== '',
            self::email    => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            self::min      => strlen((string) $value) >= (int) $config,
            self::max      => strlen((string) $value) <= (int) $config,
            self::matches  => $value === $context->{(string) $config},
        };
    }

    public function message(string $field, int|string|null $config): string
    {
        return match ($this) {
            self::required => ucfirst($field) . ' is required.',
            self::email    => 'Enter a valid email address.',
            self::min      => ucfirst($field) . " must be at least $config characters.",
            self::max      => ucfirst($field) . " must be at most $config characters.",
            self::matches  => ucfirst((string) $config) . ' does not match.',
        };
    }
}
```

### Validate Attribute

Applies one or more rules to a property. Accepts bare `Rule` enums (no config) or `[Rule, value]` arrays (with config) as variadic arguments.

```php
// src/Attributes/Validate.php
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Validate
{
    /** @var list<array{Rule, int|string|null}> */
    public array $rules;

    public function __construct(Rule|array ...$rules)
    {
        $this->rules = array_map(
            static fn(Rule|array $rule): array => $rule instanceof Rule
                ? [$rule, null]
                : $rule,
            $rules,
        );
    }
}
```

### ValidateWith Attribute

Escape hatch for arbitrary validation logic. References a class implementing `ValidationRule`.

```php
// src/Attributes/ValidateWith.php
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
readonly class ValidateWith
{
    /** @param class-string<ValidationRule> $Rule */
    public function __construct(public string $Rule) {}
}
```

```php
// src/Validation/ValidationRule.php
interface ValidationRule
{
    public function passes(mixed $value, object $context): bool;
    public function message(string $field): string;
}
```

### Validator

Reflects on any DataModel object, reads `#[Validate]` and `#[ValidateWith]` attributes, returns errors keyed by `{property}_error`.

```php
// src/Validation/Validator.php
final class Validator
{
    /** @return array<string, string> */
    public static function validate(object $model): array;
}
```

Logic:
1. `ReflectionClass::getProperties()` on the model
2. For each property, read `#[Validate]` — iterate `$rules`, call `$rule->passes($value, $config, $model)`
3. For each property, read `#[ValidateWith]` — instantiate class, call `->passes($value, $model)`
4. On first failure per property, add `"{property}_error" => message` to errors array
5. Return errors (empty array = valid)

## Before / After

### RegisterRequest — Before

```php
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;

    public const string password_confirmation = 'password_confirmation';
    public string $password_confirmation;
}
```

### RegisterRequest — After

```php
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;

    #[Validate(Rule::required)]
    public string $name;

    #[Validate(Rule::required, Rule::email)]
    public ?string $email;

    #[Validate(Rule::required, [Rule::min, 8])]
    public string $password;

    /** @see $password_confirmation */
    public const string password_confirmation = 'password_confirmation';
    #[Validate(Rule::required, [Rule::matches, 'password'])]
    public string $password_confirmation;
}
```

Properties inherited from `UserColumns` are redeclared on the request with `#[Validate]` attributes. The `#[Column]` attributes from the trait are replaced by `#[Validate]` — the request layer has different rules than the database layer.

### RegisterController::validate() — Before

```php
private function validate(RegisterRequest $RegisterRequest): array
{
    $errors = [];
    if ($RegisterRequest->name === '') {
        $errors[RegisterViewModel::name_error] = 'Name is required.';
    }
    if ($RegisterRequest->email === '') {
        $errors[RegisterViewModel::email_error] = 'Email is required.';
    } elseif (! filter_var(value: $RegisterRequest->email, filter: FILTER_VALIDATE_EMAIL)) {
        $errors[RegisterViewModel::email_error] = 'Enter a valid email address.';
    }
    if ($RegisterRequest->password === '') {
        $errors[RegisterViewModel::password_error] = 'Password is required.';
    } elseif (strlen(string: $RegisterRequest->password) < 8) {
        $errors[RegisterViewModel::password_error] = 'Password must be at least 8 characters.';
    }
    if ($RegisterRequest->password !== $RegisterRequest->password_confirmation) {
        $errors[RegisterViewModel::password_confirmation_error] = 'Passwords do not match.';
    }
    return $errors;
}
```

### RegisterController::handleRegistration() — After

```php
private function handleRegistration(ServerRequestInterface $ServerRequestInterface): ResponseInterface
{
    $body = (array) $ServerRequestInterface->getParsedBody();
    $RegisterRequest = RegisterRequest::from([
        User::name => trim((string) ($body[User::name] ?? '')),
        User::email => trim((string) ($body[User::email] ?? '')),
        User::password => (string) ($body[User::password] ?? ''),
        RegisterRequest::password_confirmation => (string) ($body[RegisterRequest::password_confirmation] ?? ''),
    ]);

    $errors = Validator::validate($RegisterRequest);
    // ... rest unchanged
}
```

The `validate()` method is deleted entirely.

## Implementation Steps

### Step 1: Create Rule enum

- File: `src/Validation/Rule.php`
- Backed string enum with cases: `required`, `email`, `min`, `max`, `matches`
- Methods: `passes(mixed $value, int|string|null $config, object $context): bool`
- Methods: `message(string $field, int|string|null $config): string`

### Step 2: Create Validate attribute

- File: `src/Attributes/Validate.php`
- `#[Attribute(Attribute::TARGET_PROPERTY)]`
- Constructor: `Rule|array ...$rules`
- Normalizes to `list<array{Rule, int|string|null}>`

### Step 3: Create ValidationRule interface

- File: `src/Validation/ValidationRule.php`
- Methods: `passes(mixed $value, object $context): bool`, `message(string $field): string`

### Step 4: Create ValidateWith attribute

- File: `src/Attributes/ValidateWith.php`
- `#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]`
- Constructor: `class-string<ValidationRule> $Rule`

### Step 5: Create Validator

- File: `src/Validation/Validator.php`
- Static method: `validate(object $model): array<string, string>`
- Reflects on properties, reads both attribute types, returns error map keyed by `{property}_error`

### Step 6: Add #[Validate] attributes to RegisterRequest

- Override `name`, `email`, `password` from `UserColumns` trait with `#[Validate]` attributes
- Keep `password_confirmation` with its own `#[Validate]`

### Step 7: Replace inline validation in RegisterController

- Delete the `validate()` method
- Replace `$this->validate($RegisterRequest)` with `Validator::validate($RegisterRequest)`
- Remove unused `RegisterViewModel` error constant references if error keys now come from `Validator`

### Step 8: Write tests

- Unit tests for `Rule::passes()` and `Rule::message()` — each case, pass and fail
- Unit test for `Validator::validate()` — happy path (no errors), each rule failure, multiple failures
- Integration test: `RegisterRequest` with `#[Validate]` attributes produces expected errors
- Verify existing `RegisterRouteTest` still passes (regression)

### Step 9: Run check:all

- `./run fix:all` to apply style and rector fixes
- `./run check:all` — all 12 checks must pass

## Error Key Convention

`Validator::validate()` returns keys as `{property_name}_error`. This matches the existing `RegisterViewModel` constants (`name_error`, `email_error`, `password_error`, `password_confirmation_error`). No mapping layer needed — the convention connects validation output to view model input.

## Scope Boundary

This plan covers:
- The validation framework (`Rule`, `Validate`, `ValidateWith`, `ValidationRule`, `Validator`)
- Migrating `RegisterRequest` and `RegisterController` to use it
- Tests for the new code

This plan does not cover:
- Manifest (`thryds.yaml`) changes — add after the pattern is validated
- Rector rules to enforce attribute-driven validation
- Deriving validation from `#[Column]` attributes automatically
- Other request types (build those when they exist)

## Files Created

| File | Type |
|---|---|
| `src/Validation/Rule.php` | Enum |
| `src/Validation/ValidationRule.php` | Interface |
| `src/Validation/Validator.php` | Class |
| `src/Attributes/Validate.php` | Attribute |
| `src/Attributes/ValidateWith.php` | Attribute |

## Files Modified

| File | Change |
|---|---|
| `src/Requests/RegisterRequest.php` | Add `#[Validate]` attributes, override trait properties |
| `src/Controllers/RegisterController.php` | Delete `validate()`, use `Validator::validate()` |
