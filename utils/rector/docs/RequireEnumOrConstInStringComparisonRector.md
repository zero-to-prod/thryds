# RequireEnumOrConstInStringComparisonRector

Flags `===` and `!==` comparisons where one operand is a raw string literal, requiring the value to be backed by an enum case or named constant.

**Category:** Magic String Elimination
**Mode:** `warn`
**Auto-fix:** No (the correct replacement — enum case or constant — cannot be inferred statically)

## Rationale

The project organizing principle is: "Constants name things, enumerations define sets." A raw string literal in an identity comparison (`=== 'POST'`, `!== 'active'`) is a magic value — it has no canonical definition, can be misspelled silently, and cannot be navigated to by tooling. Replacing it with an enum case or constant gives the value a name, a location, and compiler-checked spelling.

## What It Detects

Any `===` or `!==` comparison where either side is a string literal (`String_` node):

```php
$request->getMethod() === 'POST'
$status !== 'active'
'draft' === $post->status
```

Covered statement containers: `if`, `elseif`, `while`, `do-while`, expression statements, and `return` statements.

## Transformation

### In `auto` mode

No-op. Returns `null`. The correct replacement cannot be determined without domain knowledge.

### In `warn` mode

Adds a TODO comment to the containing statement:

```
// TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds a TODO comment |
| `message` | `string` | See source | TODO comment template. Use `%s` for the raw string value. |

## Example

### Before

```php
if ($request->getMethod() === 'POST') {
    echo 'post';
}
```

### After

```php
// TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
if ($request->getMethod() === 'POST') {
    echo 'post';
}
```

## Resolution

When you see the TODO comment from this rule:

1. Identify the set of values this string belongs to (e.g. HTTP methods, statuses, roles).
2. If the set is closed and predefined, define a backed enum: `enum HttpMethod: string { case POST = 'POST'; }`.
3. If the value is a single named thing (not part of a set), define a constant: `public const string POST = 'POST';`.
4. Replace the raw string literal with the enum case (`HttpMethod::POST`) or constant reference.

## Caveats

- Only the first raw string comparison per statement triggers the comment (subsequent ones in the same `&&`/`||` chain are reported on the next `fix:rector` run).
- Comparisons inside closures or arrow functions that are themselves inside a flagged statement are traversed, so a closure's comparison may cause the outer statement to be flagged.

## Related Rules

- `ForbidMagicStringArrayKeyRector` — flags raw string array keys
- `ForbidStringComparisonOnEnumPropertyRector` — flags comparisons of known enum properties against raw strings (auto-fixable when the enum is configured)
- `SuggestEnumForStringPropertyRector` — suggests introducing an enum for string-typed properties
