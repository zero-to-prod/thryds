# ForbidCrossFileStringDuplicationRector

Flags string literals that appear in 3 or more distinct files, signalling they should be extracted to a shared constant.

**Category:** Magic String Elimination
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

"One place to change behaviour." When the same magic string lives in a PHP class, a Blade template, and a test file, changing the string requires touching all three places. This rule extends `SuggestDuplicateStringConstantRector` (which catches per-file duplication) to the cross-file dimension: if the same string literal is scattered across 3 or more files, it is a strong signal that the value belongs in a named constant.

## What It Detects

A string literal that:

- Is at least 4 characters long
- Is not numeric
- Is not in the built-in exclusion list (common fillers like `'utf-8'`, `'null'`, HTTP methods, etc.)
- Does not contain `<` or `>` (HTML/XML fragments)
- Does not start with `/` or `http` (file paths, URLs)
- Is **not** the value of a `const` or `define()` declaration (the constant itself being the single source of truth)
- Appears in 3 or more distinct files in the configured paths

## Detection Strategy

The rule uses a two-phase approach within a single Rector run:

1. **Collect phase** — on each `FileNode` visit, record qualifying string values alongside their file path in a static accumulator (`$filesByValue`).
2. **Annotate phase** — in the same visit, flag any statement whose string value is already seen in `minFiles` or more distinct files (based on accumulated state so far).

Because Rector processes files sequentially, the first few files may not yet have complete cross-file counts. Running Rector a second time (as `fix:rector` does on repeated runs) ensures all files are annotated correctly. The rule is **idempotent** — re-running never duplicates comments.

## Transformation

This rule is **warn-only**. The right constant location is context-dependent and cannot be automated.

### In `auto` mode

No-op — returns `null` immediately.

### In `warn` mode

Prepends a TODO comment to the first statement in the file that contains the repeated string:

```php
// TODO: [ForbidCrossFileStringDuplicationRector] string 'button-primary' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
$class = 'button-primary';
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds TODO comments |
| `minFiles` | `int` | `3` | Minimum number of distinct files before flagging |
| `message` | `string` | see below | TODO comment text. Supports `%s` (string value) and `%d` (file count) placeholders |
| `preSeededFilesByValue` | `array<string, string[]>` | `[]` | Pre-populate the collector (used in tests) |

Default message:
```
TODO: [ForbidCrossFileStringDuplicationRector] string '%s' appears in %d files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
```

## Example

### Before (in `tests/Integration/SomeTest.php`)

```php
$class = 'button-primary';
```

The same string `'button-primary'` appears in `src/UI/ButtonVariant.php` and `resources/views/components/button.blade.php`.

### After

```php
// TODO: [ForbidCrossFileStringDuplicationRector] string 'button-primary' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
$class = 'button-primary';
```

## Resolution

When you see the TODO comment from this rule:

1. Identify all files containing the flagged string (run `grep -r "'button-primary'" src/ tests/`).
2. Determine which class or enum owns this value semantically (e.g. `ButtonVariant`, a CSS constants class, etc.).
3. Define a `public const string` on that class:
   ```php
   public const string PRIMARY = 'button-primary';
   ```
4. Replace all raw string literals with `ClassName::PRIMARY`.
5. The Rector rule `StringArgToClassConstRector` can automate step 4 once the constant exists and is registered.

## Exclusions

The following strings are never flagged:
- Strings shorter than 4 characters
- Numeric strings (`'1234'`, `'99.99'`)
- Common fillers: `''`, `' '`, `'true'`, `'false'`, `'null'`, `'utf-8'`, `'UTF-8'`, `'application/json'`, date formats, etc.
- HTML/XML fragments (strings containing `<` or `>`)
- File paths (starting with `/`) and URLs (starting with `http`)
- Strings that are constant values themselves (`public const string FOO = 'value'` — the constant IS the single source of truth)

## Related Rules

- `SuggestDuplicateStringConstantRector` — detects duplicates within a single file
- `ForbidMagicStringArrayKeyRector` — flags string array keys that should be constants
- `StringArgToClassConstRector` — replaces string literals with class constant references (auto-fix)
