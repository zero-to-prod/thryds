# RequireViewModelDataInMakeCallRector

Flags `Blade->make()` calls that render a view with a matching ViewModel class
but omit the `data:` named argument, ensuring every view that has typed context
actually receives it.

**Category:** DataModel & ViewModel
**Mode:** `warn`
**Auto-fix:** No — the rule cannot construct the data array; only a developer can
know which domain objects to pass.

## Rationale

When a ViewModel class exists for a view, it defines the typed contract between
the controller and the template. Omitting `data:` silently breaks that contract —
the view renders without the expected variables. Auto-fixing is not feasible
because the rule cannot infer the actual data values.

## What It Detects

A `->make()` call that:

1. Passes a `view:` argument of the form `ViewEnum::case->value`.
2. Has a class `<PascalCase>ViewModel` in the configured namespace that matches
   the enum case name.
3. Does **not** pass a `data:` named argument.

```php
// ViewModel exists: RegisterViewModel
$Blade->make(view: View::register->value);  // ← flagged
```

A call that already has `data:` is fine and any stale TODO comment is removed:

```php
$Blade->make(
    view: View::register->value,
    data: [RegisterViewModel::view_key => RegisterViewModel::from([...])],
);
```

## Transformation

### In `warn` mode

A TODO comment is prepended to the offending call:

```php
// TODO: [RequireViewModelDataInMakeCallRector] make() renders 'register' which has a RegisterViewModel — pass data: [RegisterViewModel::view_key => RegisterViewModel::from([...])] so the view receives typed context. See: utils/rector/docs/RequireViewModelDataInMakeCallRector.md
$Blade->make(view: View::register->value);
```

The comment is automatically removed the next time Rector runs once the `data:`
argument is present.

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `viewEnumClass` | `string` | `''` | Fully-qualified class name of the View enum (e.g. `View::class`). |
| `viewModelsNamespace` | `string` | `''` | Namespace to probe for `<PascalCase>ViewModel` classes. |
| `methodName` | `string` | `'make'` | Method name to inspect (usually `make`). |
| `viewParamName` | `string` | `'view'` | Named arg that holds the view value. |
| `dataParamName` | `string` | `'data'` | Named arg that holds the data array. |
| `viewModelSuffix` | `string` | `'ViewModel'` | Suffix appended to the PascalCase case name to form the class name. |
| `mode` | `string` | `'warn'` | Only `'warn'` is supported. `'auto'` is a no-op. |
| `message` | `string` | see source | TODO text. Supports four `%s` placeholders: case name, ViewModel short name, ViewModel short name, ViewModel short name. |

## Resolution

When you see the TODO comment from this rule:

1. Identify the ViewModel: `<PascalCase>ViewModel` in the ViewModels namespace.
2. Add the `data:` argument to the `make()` call:
   ```php
   $Blade->make(
       view: View::register->value,
       data: [RegisterViewModel::view_key => RegisterViewModel::from([...])],
   );
   ```
3. Run `./run fix:rector` — the TODO comment is removed automatically.

## Caveats

- The rule uses `class_exists()` at analysis time, so the ViewModel class must
  be autoloadable when Rector runs.
- Case-name to class-name mapping uses `ucwords` with `_` as the word separator,
  then strips underscores (e.g. `my_page` → `MyPageViewModel`).

## Related Rules

- `RequireViewEnumInMakeCallRector` — enforces that `make()` uses a View enum
  rather than a raw string.
- `AddViewModelAttributeRector` — adds `#[ViewModel]` to classes in the
  ViewModels namespace.
