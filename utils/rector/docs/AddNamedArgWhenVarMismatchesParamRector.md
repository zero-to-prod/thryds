# AddNamedArgWhenVarMismatchesParamRector

Add a named argument label when a variable name does not match the parameter name it is passed to.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

When a variable holding a value of type `TaskPayload` is named `$TaskPayload` but the parameter it maps to is named `$payload`, the call site `run($TaskPayload)` is ambiguous — the reader cannot tell whether the variable name or the parameter name represents the authoritative concept. Making the mapping explicit with `run(payload: $TaskPayload)` removes the ambiguity and makes the call self-documenting. This also future-proofs the call against parameter reordering, which is a silent source of bugs.

Once any argument in a call is named, all subsequent positional arguments are also named to maintain PHP's positional-then-named constraint.

## What It Detects

A call (`method`, `static`, `function`, or `new`) where at least one positional argument is a variable whose name differs from the parameter name it occupies, e.g. `$runner->run($TaskPayload)` where the parameter is `$payload`.

## Transformation

### In `auto` mode
Adds the parameter name as the named-argument label: `run(payload: $TaskPayload)`. Any positional arguments that appear after the first mismatch are also labelled, even if their own names match.

### In `warn` mode
Adds the configured `message` as a `//` comment above the call statement (idempotent — not added if it is already present).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
$TaskRunner = new TaskRunner();
$TaskPayload = new TaskPayload();
$TaskRunner->run($TaskPayload);
```

### After
```php
$TaskRunner = new TaskRunner();
$TaskPayload = new TaskPayload();
$TaskRunner->run(payload: $TaskPayload);
```

### Before (mixed args — one mismatch cascades)
```php
$LogWriter->write($LogEntry, $channel);
// $LogEntry mismatches param $entry; $channel matches but must also be named
```

### After
```php
$LogWriter->write(entry: $LogEntry, channel: $channel);
```

## Related Rules

- [`RemoveNamedArgWhenVarMatchesParamRector`](RemoveNamedArgWhenVarMatchesParamRector.md) — the inverse: strips redundant named labels when the variable name already matches the parameter name
- [`RenameParamToMatchTypeNameRector`](RenameParamToMatchTypeNameRector.md) — renames parameters to match their type, which reduces mismatches caught by this rule
