# RequireConstForRepeatedArrayKeyRector

Flags string array keys that appear 2 or more times in the same file, prompting extraction to a named class constant.

**Category:** Magic String Elimination
**Mode:** `warn`
**Auto-fix:** No

## Rationale

The project principle "Constants name things" applies with particular force to repeated strings. A string key used once might be incidental; a key used two or more times across statements in the same file is a de facto concept that deserves a name. Without a constant, every occurrence must be updated independently during a rename, typos in one copy silently diverge from others, and the semantic meaning of the key is never stated explicitly.

This rule acts as a file-scoped duplication detector for array keys, both in array literals (`['status_code' => 200]`) and in dimension fetches (`$data['status_code']`). On the first-occurrence statement it adds a comment that names the key, its total count, and the recommended action.

Calls to excluded classes (e.g., `Log`, `OpcacheStatus`) are exempt because those call sites have dedicated rules that already enforce their own key constants.

## What It Detects

String keys used as:
- Array literal item keys: `['status_code' => 200]`
- Array dimension subscripts: `$data['opcache_statistics']`

The key must meet two eligibility conditions:
- `strlen($key) >= minLength` (default: 3, excludes trivial keys like `'id'`)
- Not in the `excludedKeys` list (project excludes `'class'`, `'mode'`, `'message'`)

The comment is added to the **first statement** in the file that contains the repeated key.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the first statement in the file that contains the repeated key. The comment includes the key value and the total occurrence count. The template uses `%s` for the key and `%d` for the count.

Project-configured comment:
```
// TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `"TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant."` | Comment template; `%s` = key, `%d` = count |
| `minOccurrences` | `int` | `2` | Minimum number of uses in the file before flagging |
| `minLength` | `int` | `3` | Minimum key length; shorter keys are ignored |
| `excludedKeys` | `string[]` | `[]` | Specific key values to skip regardless of occurrence count |
| `excludedClasses` | `string[]` | `[]` | Class names whose method/static call array arguments are skipped |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(RequireConstForRepeatedArrayKeyRector::class, [
    'minOccurrences' => 2,
    'minLength' => 3,
    'excludedKeys' => ['class', 'mode', 'message'],
    'excludedClasses' => [Log::class, OpcacheStatus::class],
    'mode' => 'warn',
    'message' => "TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant.",
]);
```

## Example

### Before (array dimension fetch)
```php
$a = $data['opcache_statistics']['hits'];
$b = $data['opcache_statistics']['misses'];
```

### After (array dimension fetch)
```php
// TODO: [RequireConstForRepeatedArrayKeyRector] 'opcache_statistics' used 2x as array key — extract to a class constant.
$a = $data['opcache_statistics']['hits'];
$b = $data['opcache_statistics']['misses'];
```

### Before (array item key)
```php
$a = ['status_code' => 200];
$b = ['status_code' => 404];
```

### After (array item key)
```php
// TODO: [RequireConstForRepeatedArrayKeyRector] 'status_code' used 2x as array key — extract to a class constant.
$a = ['status_code' => 200];
$b = ['status_code' => 404];
```

## Resolution

When you see the TODO comment from this rule:
1. Identify which class is the canonical owner of this key (the class that defines the data structure the key belongs to).
2. Add `public const string KEY_NAME = 'key_value';` to that class.
3. Replace every string literal occurrence with `ClassName::KEY_NAME`.
4. If no single class owns the key, create a dedicated constants class or consider whether the pattern warrants a typed data class.

## Related Rules

- [`ForbidMagicStringArrayKeyRector`](ForbidMagicStringArrayKeyRector.md) — flags every magic string array key regardless of repetition count
