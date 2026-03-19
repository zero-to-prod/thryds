# MigrateAddCaseListToHeredocRector

Migrates `addCase` attribute arguments from inline numbered-list strings to a heredoc with each numbered item on its own line.

**Category:** Code Quality / Migration
**Mode:** `auto` (configurable)
**Auto-fix:** Yes

## Rationale

`ClosedSet` and `SourceOfTruth` attributes accept an `addCase` argument — a checklist of steps to follow when adding a new enum case or extending an entity. These checklists are often encoded as a single inline string (e.g. `'1. Do X. 2. Do Y. 3. Do Z.'`), which is hard to read and diff. A heredoc with one item per line is easier to scan and review.

## What It Detects

An `Attribute` node that has a named `addCase` argument whose value is a plain string starting with `^\d+\.` (a numbered list pattern).

## Transformation

### In `auto` mode

Splits the string on `. ` followed by a digit+dot, trims each item, and rewrites the argument value as an indented heredoc with `TEXT` as the label.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite the string; `'warn'` to add a TODO comment instead |
| `message` | `string` | `''` | TODO comment text (only used in `'warn'` mode) |

## Example

### Before

```php
#[ClosedSet(Domain::blade_components, addCase: '1. Add enum case. 2. Create templates/components/{case}.blade.php with @props. 3. Add example to styleguide template.')]
enum Foo: string
{
    case bar = 'bar';
}
```

### After

```php
#[ClosedSet(Domain::blade_components, addCase: <<<TEXT
    1. Add enum case.
    2. Create templates/components/{case}.blade.php with @props.
    3. Add example to styleguide template.
TEXT)]
enum Foo: string
{
    case bar = 'bar';
}
```

## Skip Cases

- The `addCase` value is already a heredoc or nowdoc — no change.
- The string does not start with a digit+dot pattern — no change.
- No `addCase` named argument is present on the attribute — no change.

## Related Rules

- `ValidateChecklistPathsRector` — validates that file paths referenced in `addCase`/`addKey` arguments actually exist.
