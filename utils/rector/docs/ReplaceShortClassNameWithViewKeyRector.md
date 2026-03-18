# ReplaceShortClassNameWithViewKeyRector

Replaces `short_class_name(SomeClass::class)` function-call array keys and plain string keys paired with `SomeClass::from()` values with `SomeClass::view_key` constant fetches.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

View data arrays that nest ViewModel instances often use the ViewModel's class name as the array key, allowing Blade templates to access `$data['ErrorViewModel']`. The transitional form using `short_class_name(ErrorViewModel::class)` as the key is runtime-computed and invisible to static analysis. The project convention is to use the `view_key` class constant instead (`ErrorViewModel::view_key`), which:
- Is statically resolvable (IDE navigation, rename refactoring)
- Documents the intent directly in the class
- Keeps the key and the class in sync without runtime computation

This rule handles two patterns: `short_class_name(Class::class) => ...` and `'StringKey' => Class::from([...])` (where the string matches the class's `view_key`).

## What It Detects

Array items where:
1. The key is a call to the configured `shortClassNameFunction` with a `SomeClass::class` argument, and `SomeClass` has a `view_key` constant, **or**
2. The key is a plain string and the value is a `SomeClass::from(...)` static call, and `SomeClass` has a `view_key` constant

## Transformation

### In `auto` mode

The key expression is replaced with `\SomeClass::view_key`.

**Pattern 1 — function call key:**
```php
// Before
$data = [
    short_class_name(SomeViewModel::class) => new SomeViewModel(),
];

// After
$data = [
    \SomeViewModel::view_key => new SomeViewModel(),
];
```

**Pattern 2 — string key with static from() value:**
```php
// Before
$data = [
    'model' => ViewModelWithKey::from([...]),
];

// After
$data = [
    \ViewModelWithKey::view_key => ViewModelWithKey::from([...]),
];
```

### In `warn` mode

A TODO comment is prepended to the affected key expression. No replacement occurs.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `shortClassNameFunction` | `string` | `'short_class_name'` | Fully-qualified (or unqualified) function name to detect |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(ReplaceShortClassNameWithViewKeyRector::class, [
    'shortClassNameFunction' => 'ZeroToProd\\Thryds\\Helpers\\short_class_name',
    'mode' => 'auto',
]);
```

## Example

### Before
```php
$data = [
    short_class_name(SomeViewModel::class) => new SomeViewModel(),
];
```

### After
```php
$data = [
    \SomeViewModel::view_key => new SomeViewModel(),
];
```

## Resolution

When you see the TODO comment from this rule:
1. Confirm the ViewModel class has a `view_key` constant (add one with [`AddViewKeyConstantRector`](AddViewKeyConstantRector.md) if missing).
2. Replace `short_class_name(SomeClass::class)` or the plain string key with `SomeClass::view_key`.
3. Remove the TODO comment.

## Related Rules

- [`AddViewKeyConstantRector`](AddViewKeyConstantRector.md) — adds the `view_key` constant that this rule depends on
- [`RequireViewModelAttributeOnDataModelRector`](RequireViewModelAttributeOnDataModelRector.md) — enforces the `#[ViewModel]` attribute on the same class
- [`UseClassConstArrayKeyForDataModelRector`](UseClassConstArrayKeyForDataModelRector.md) — replaces string keys in `::from()` arrays with property constants
