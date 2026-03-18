# DetectParallelBladePhpBehaviorRector

Detects string literals in PHP files that duplicate a value already defined as a class constant or backed enum case value elsewhere in the codebase — the cross-layer coupling problem.

**Category:** Magic String Elimination
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

"One place to change behaviour." When a string value is defined as a constant (or enum case) in one PHP file but then appears as a raw string literal in another PHP file (e.g. a view helper, Blade component class, or presenter), there are two places to change. The constant is the source of truth; the duplicate literal is the risk.

This rule is particularly valuable at the PHP/Blade boundary: PHP defines the constant, and somewhere in the PHP rendering layer the same string is hardcoded instead of using the constant reference.

**Note:** We are not parsing Blade templates directly. We flag raw string literals in PHP that duplicate values from PHP constants/enum cases.

## What It Detects

A string literal in a PHP file that:

- Is at least 4 characters long
- Does not contain spaces (natural-language phrases are excluded)
- Is not numeric
- Is not in the built-in exclusion list (HTTP methods, common fillers, date formats, etc.)
- Does not contain `<` or `>` (HTML/XML fragments)
- Does not start with `/` or `http` (file paths, URLs)
- Matches a known constant value or backed enum case value defined in **another** file
- Is **not** the constant/enum definition itself

## Detection Strategy

The rule uses a two-phase approach within a single Rector run, accumulating state via a static registry:

1. **Collect phase** — on each `FileNode` visit, record all qualifying class constant and backed enum case string values. The registry maps `value => {class FQN, const name}`.
2. **Annotate phase** — in the same visit, flag any statement containing a `String_` literal that matches a value already in the registry (collected from a different file).

Because Rector processes files sequentially, the registry grows during the run. Running Rector a second time (as `fix:rector` does on repeated runs) ensures all files are annotated. The rule is **idempotent** — re-running never duplicates comments.

## Transformation

This rule is **warn-only**. The right constant reference is context-dependent and cannot be automated.

### In `auto` mode

No-op — returns `null` immediately.

### In `warn` mode

Prepends a TODO comment to the statement containing the duplicate string:

```php
// TODO: [DetectParallelBladePhpBehaviorRector] Use App\UI\ButtonVariant::Primary instead of hardcoded 'primary'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
$variant = 'primary';
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds TODO comments |
| `message` | `string` | see below | TODO comment text. Supports `%s` (class FQN), `%s` (const name), `%s` (string value) placeholders |
| `preSeededValues` | `array<string, array{class: string, const: string}>` | `[]` | Pre-populate the registry (used in tests to simulate constants from other files) |

Default message:
```
TODO: [DetectParallelBladePhpBehaviorRector] Use %s::%s instead of hardcoded '%s'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
```

## Examples

### Before (`src/UI/Presenters/ButtonPresenter.php`)

```php
$variant = 'primary';
```

The value `'primary'` is already defined in `src/UI/ButtonVariant.php`:

```php
enum ButtonVariant: string
{
    case Primary = 'primary';
}
```

### After

```php
// TODO: [DetectParallelBladePhpBehaviorRector] Use App\UI\ButtonVariant::Primary instead of hardcoded 'primary'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
$variant = 'primary';
```

## Resolution

When you see the TODO comment from this rule:

1. Identify the constant/enum referenced in the message (e.g. `App\UI\ButtonVariant::Primary`).
2. Replace the hardcoded string with the constant reference:
   ```php
   $variant = ButtonVariant::Primary->value;  // backed enum
   // or
   $variant = ButtonVariant::PRIMARY;          // class constant
   ```
3. Add the required `use` import if needed.
4. The Rector rule `StringArgToClassConstRector` can automate step 2 once the constant is registered in its `mappings` config.

## Exclusions

The following strings are never flagged:

- Strings shorter than 4 characters
- Strings containing spaces (natural-language phrases)
- Numeric strings
- Common fillers: `''`, `' '`, `'true'`, `'false'`, `'null'`, `'utf-8'`, HTTP methods, date formats, etc.
- HTML/XML fragments (strings containing `<` or `>`)
- File paths (starting with `/`) and URLs (starting with `http`)
- The constant/enum definition itself (the first occurrence IS the source of truth)
- Literals inside the class/enum that defines the constant

## Caveats

- **First-definition wins:** the registry records the first class/enum seen with a given value. If two files define the same string constant, the second is not tracked as a "definition" — it may itself receive a TODO comment.
- **Sequential processing:** within a single run, a file processed before its defining class will not be flagged until the next run. Use `fix:rector` (which runs multiple passes) for complete coverage.
- **Short name resolution:** the rule uses `namespacedName` (populated by PhpParser's `NameResolver`) for FQN display. Files without namespaces show bare class names.

## Related Rules

- `ForbidCrossFileStringDuplicationRector` — flags string literals appearing in 3+ distinct files (quantity-based)
- `SuggestDuplicateStringConstantRector` — detects duplicate strings within a single file
- `ForbidMagicStringArrayKeyRector` — flags string array keys that should be constants
- `StringArgToClassConstRector` — replaces string literals with class constant references (auto-fix, requires mapping config)
