# Migration: Attribute-Driven Manifest System

## Goal

Replace convention-based template parsing (regex on `@extends`, `<x-*>`, `@props`, `use` imports) with PHP attributes as the sole source of structural metadata. Introduce `thryds.yaml` as a declarative manifest that maps 1:1 to the attribute graph. Enforcement is a single graph diff: `desired(thryds.yaml) vs actual(inventory via reflection)`.

## Current State

- **Attributes cover**: routes (`#[RouteInfo]`, `#[RouteOperation]`, `#[DevOnly]`), controllers (`#[Persists]`, `#[RedirectsTo]`), tables (`#[Table]`, `#[Column]`, `#[PrimaryKey]`, `#[ForeignKey]`, `#[Index]`), viewmodels (`#[ViewModel]`), enums (`#[ClosedSet]`), migrations (`#[Migration]`), requirements (`#[Requirement]`)
- **Conventions cover** (no attributes): view→layout (`@extends`), view→component (`<x-*>`), view→viewmodel (`use` import), component props (`@props`), test→route coverage (`Route::` references), page titles (`@section('title', ...)`)
- **`inventory.php`** builds the full graph by mixing reflection (attributes) + regex (templates). It outputs JSON or DOT.
- **`RouteRegistrar`** hardcodes `HomeController` and uses convention matching (`Route::name` → `View::name`)
- **`check-all.php`** runs 11 checks + tests, outputs structured JSON

## Target State

- **6 new attributes** fill the convention gaps — every structural relationship is attribute-declared
- **`inventory.php`** reads only attributes via reflection — no template parsing
- **`thryds.yaml`** at project root declares the desired project graph
- **`check:manifest`** diffs `thryds.yaml` against inventory, fails on any delta
- **`sync:manifest`** scaffolds code for entries in `thryds.yaml` missing from code
- **`fix:all`** includes manifest sync; **`check:all`** includes manifest check

## Phases

Each phase is independently shippable. Each leaves `check:all` green.

| Phase | File | Summary | New/Modified Files |
|---|---|---|---|
| 1 | `01-new-attributes.md` | Create 6 new attribute classes | 6 new in `src/Attributes/` |
| 2 | `02-apply-attributes.md` | Apply attributes to View, Component, test classes | `View.php`, `Component.php`, 11 test files |
| 3 | `03-inventory-attributes-only.md` | Refactor inventory to read attributes, drop regex | `inventory.php` |
| 4 | `04-yaml-manifest-schema.md` | Define `thryds.yaml` schema, write parser, create initial manifest | `thryds.yaml`, new parser script |
| 5 | `05-graph-diff-engine.md` | Build diff(desired, actual), `check:manifest`, `sync:manifest` | 3 new scripts, `composer.json` |
| 6 | `06-pipeline-integration.md` | Wire into `fix:all` and `check:all` | `check-all.php`, `fix-all.php`, `CLAUDE.md` |

## Enforcement Model

```
thryds.yaml ──parse──→ Graph B (desired)
                              │
                           diff(A, B) → structured JSON delta
                              │
attributes ──inventory──→ Graph A (actual)
```

Delta categories:
- `missing_from_code` — in manifest, not in attributes (needs sync)
- `missing_from_manifest` — in attributes, not in manifest (needs declaration)
- `property_drift` — both exist, values differ
- `edge_drift` — relationship exists in one but not the other

## Constraints

- Every phase must pass `./run check:all` before and after
- No template regex parsing after Phase 3 is complete
- No backwards-compatibility shims — old patterns are replaced, not wrapped
- Attributes are the single source of truth; manifest is a projection check against them
