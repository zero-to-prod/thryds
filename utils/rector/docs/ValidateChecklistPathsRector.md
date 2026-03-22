# ValidateChecklistPathsRector

Validates that file paths referenced inside `addCase` / `addKey` checklist strings on configured attributes (`#[SourceOfTruth]`, `#[ClosedSet]`, `#[KeyRegistry]`) actually exist on disk.

**Category:** Checklist Validation
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

Backed enums and key registries carry `#[ClosedSet(addCase: '...')]` or `#[KeyRegistry(addKey: '...')]` attributes that describe the steps a developer must follow when adding a new case or key. These checklists reference specific files that need updating (e.g., `scripts/sync-preload.php`, `templates/index.blade.php`). Over time, files are moved or renamed, leaving the checklist pointing at paths that no longer exist. This rule catches stale path references at Rector time — before they mislead the next developer.

## What It Detects

`Class_` and `Enum_` nodes that have one of the configured attributes with a matching named argument whose value is a string. The rule extracts all tokens from that string that look like file paths (matching extensions: `.php`, `.yaml`, `.yml`, `.json`, `.js`, `.ts`, `.env`, `.example`, `.blade.php`). For each extracted path, it checks existence relative to `projectDir`, also trying common prefixes (`scripts/`, `src/`, `public/`, `templates/`). Template placeholders like `{case}` are skipped.

A violation is raised for each path that cannot be found on disk.

## Transformation

### In `warn` mode

For each missing path, a TODO comment is prepended to the class or enum declaration:

```
// TODO: [ValidateChecklistPathsRector] Example references 'scripts/nonexistent.php' in addCase, but this file does not exist. Update the checklist.
```

Multiple missing paths produce multiple comment lines. Comments for paths already present in existing comments are not duplicated.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `attributes` | `array` | `[]` | List of `{attributeClass: string, paramName: string}` pairs to inspect |
| `projectDir` | `string` | `''` | Absolute path to the project root used to resolve relative paths |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | see source | `sprintf` template; receives `(className, path, paramName)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ValidateChecklistPathsRector::class, [
    'attributes' => [
        ['attributeClass' => SourceOfTruth::class, 'paramName' => 'addCase'],
        ['attributeClass' => ClosedSet::class,     'paramName' => 'addCase'],
        ['attributeClass' => KeyRegistry::class,   'paramName' => 'addKey'],
    ],
    'projectDir' => __DIR__,
    'mode' => 'warn',
]);
```

## Example

### Before

```php
#[SourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}
```

### After

```php
// TODO: [ValidateChecklistPathsRector] Example references 'scripts/nonexistent.php' in addCase, but this file does not exist. Update the checklist.
#[SourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}
```

## Resolution

When you see the TODO comment from this rule:
1. Check whether the referenced file was moved or renamed — if so, update the `addCase` / `addKey` string with the new path.
2. If the file was deleted and the step is no longer needed, remove that step from the checklist string.
3. If the file should exist but does not yet, create it.
4. Run `./run check:all` after updating the checklist to confirm the comment is no longer produced.

## Related Rules

- [`RequireClosedSetOnBackedEnumRector`](RequireClosedSetOnBackedEnumRector.md) — requires `#[ClosedSet]` on backed enums; `addCase` is typically provided there
- [`RequireNamesKeysOnConstantsClassRector`](RequireNamesKeysOnConstantsClassRector.md) — requires `#[NamesKeys]` / `#[KeyRegistry]`; `addKey` is provided there
