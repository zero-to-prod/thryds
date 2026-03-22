# EnforceLayerCoverageRector

Every first-level namespace directory under `src/` must have a corresponding `Layer` enum case for attribute graph visibility.

**Category:** Discoverability
**Mode:** `warn`
**Auto-fix:** No

## Rationale

The `Layer` enum drives the `--layer=` filter in `./run list:attributes`. If a namespace directory exists under `src/` without a corresponding case, all classes in that namespace are invisible to layer-based graph queries. This was the root cause of the `Queries` layer being unreachable despite containing 17 classes.

## What It Detects

Scans first-level subdirectories of `src/` and compares them against the namespace segments covered by `Layer` enum cases (accounting for `#[Segment]` overrides). Flags any uncovered directories.

## Transformation

### In `warn` mode

Adds one TODO comment per uncovered segment on the `Layer` enum:

```
// TODO: [EnforceLayerCoverageRector] Namespace segment "Queries" has no corresponding Layer enum case — add one to ensure attribute graph visibility.
```

Stale comments from previous runs are replaced automatically.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `layerEnum` | `string` | `'Layer'` | Short or fully-qualified name of the Layer enum |
| `segmentAttribute` | `string` | `'Segment'` | Short or fully-qualified name of the Segment attribute |
| `srcDir` | `string` | `'src'` | Source directory to scan for namespace subdirectories |
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment |
| `message` | `string` | *(see default)* | `sprintf`-compatible message; `%s` is replaced with the segment name |

## Example

### Before

```php
enum Layer: string
{
    case controllers = 'controllers';
    case schema = 'schema';
}
```

### After

```php
// TODO: [EnforceLayerCoverageRector] Namespace segment "Queries" has no corresponding Layer enum case — add one to ensure attribute graph visibility.
enum Layer: string
{
    case controllers = 'controllers';
    case schema = 'schema';
}
```

## Resolution

When you see the TODO comment from this rule:

1. Add a new case to `Layer` where the backing value is the lowercase layer name.
2. If the namespace segment differs from `ucfirst(case_name)`, add `#[Segment('ActualNamespace')]`.
3. Verify with `./run list:attributes -- --layer=<new_layer>` that the classes appear.

## Related Rules

None yet.
