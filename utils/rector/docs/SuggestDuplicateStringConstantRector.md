# SuggestDuplicateStringConstantRector

Flags string literals that appear two or more times in a file by adding a TODO comment on the first occurrence, directing the developer to extract the value to a single constant.

**Category:** Code Quality / Magic String Elimination
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

Constants name things. When the same string literal appears in multiple places, every future change requires finding and updating every copy. A duplicated string is both a maintenance hazard and a signal that a concept has not yet been named. The project philosophy — "Consts name things, enums limit choices, attributes define properties" — means a repeated string should become a public constant on the appropriate class.

Strings that are the values of existing `const` declarations, `define()` calls, or `ClassConst` nodes are excluded, since they are already the source of truth.

## What It Detects

`FileNode` containing two or more identical string literals (minimum 3 characters) in non-constant context. The first statement that contains the string receives the TODO comment.

```php
$a = doSomething('application/json');
$b = getHeader('application/json');
```

## Transformation

### In `warn` mode

Adds `// TODO: Refactor duplicate string '<value>' (used Nx) to a constant` above the first statement containing the repeated string. The exact text is configured via `message` with `%s` (value) and `%d` (count) placeholders.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` disables the rule entirely |
| `message` | `string` | `"TODO: Refactor duplicate string '%s' (used %dx) to a constant"` | Comment template; `%s` = value, `%d` = count |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(SuggestDuplicateStringConstantRector::class, [
    'mode' => 'warn',
    'message' => "TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string '%s' (used %dx) to a single source of truth. Consts name things, enums limit choices, attributes define properties.",
]);
```

## Example

### Before
```php
$a = doSomething('application/json');
$b = getHeader('application/json');
```

### After
```php
// TODO: Refactor duplicate string 'application/json' (used 2x) to a constant
$a = doSomething('application/json');
$b = getHeader('application/json');
```

### Three occurrences
```php
// TODO: Refactor duplicate string 'text/html' (used 3x) to a constant
$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');
```

## Resolution

When you see the TODO comment:
1. Decide which class owns the concept that the string represents.
2. Add `public const string MY_CONSTANT = 'the-value';` to that class.
3. Replace every occurrence of the string literal in the file with `ClassName::MY_CONSTANT`.
4. If the string represents a member of a closed set (e.g. content types, status labels), consider a backed enum instead.

## Related Rules

- [`SuggestConstArrayToEnumRector`](SuggestConstArrayToEnumRector.md) — escalates from duplicate strings to suggesting enum migration for const arrays
- [`SuggestEnumForStringPropertyRector`](SuggestEnumForStringPropertyRector.md) — flags string properties on DataModel classes whose values repeat across call sites
- [`ExtractRepeatedExpressionToVariableRector`](ExtractRepeatedExpressionToVariableRector.md) — similar principle applied to repeated function calls rather than literal strings
