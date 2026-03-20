# Phase 3: Refactor Inventory to Read Attributes Only

## Objective

Refactor `scripts/inventory.php` to derive the entire dependency graph from PHP attributes via reflection. Remove all template regex parsing (`parseTemplate()`, `parseComponentProps()`). After this phase, the inventory graph is a pure projection of the attribute layer.

## Prerequisites

- Phase 2 complete: all View, Component, and test classes carry the new attributes

## Background: What Inventory Currently Does

`scripts/inventory.php` (600 lines) builds a graph with two data sources:

**Reflection-based (keeping):**
- Route nodes from `Route` enum + `#[RouteInfo]`, `#[RouteOperation]`, `#[DevOnly]`
- Controller→model edges from `#[Persists]`
- Controller→route edges from `#[RedirectsTo]`
- Model nodes from `#[Table]`, `#[Column]`
- ViewModel nodes from `#[ViewModel]` + property reflection
- UI enum nodes from `#[ClosedSet]` + backed values

**Regex-based (removing):**
- `parseTemplate()` function (lines 99-128) — regex for `@extends`, `<x-*>`, `use ...ViewModels\*`, `use ...UI\*`, `Route::*`
- `parseComponentProps()` function (lines 60-92) — regex for `@props([...])`
- Template walking loop (lines 222-260) — calls `parseTemplate()` per view
- Component template walking loop (lines 263-279) — calls `parseComponentProps()` per component
- Test file scanning (lines 489-511) — regex for `Route::*` in test bodies

## Deliverables

### 1. Remove `parseTemplate()` and `parseComponentProps()` functions

Delete the two functions entirely (lines 60-128). They are replaced by attribute reflection.

### 2. Replace view template walking with View enum attribute reflection

**Remove** (lines 222-260): the loop that reads each `templates/*.blade.php` and calls `parseTemplate()`.

**Replace with** reflection on View enum cases using the new attributes:

```php
// Walk each View enum case — relationships come from attributes, not templates.
foreach (View::cases() as $View) {
    $viewId = 'view:' . $View->value;
    $addNode($viewId, 'view', $View->value);

    $ref = new ReflectionEnumUnitCase(View::class, $View->name);

    // #[ExtendsLayout('base')] → extends edge
    $layoutAttrs = $ref->getAttributes(ExtendsLayout::class);
    if ($layoutAttrs !== []) {
        $layoutName = $layoutAttrs[0]->newInstance()->layout;
        $layoutId = 'layout:' . $layoutName;
        $addNode($layoutId, 'layout', $layoutName);
        $addEdge($viewId, $layoutId, 'extends');
    }

    // #[UsesComponent(Component::card, Component::button)] → uses edges
    $componentAttrs = $ref->getAttributes(UsesComponent::class);
    if ($componentAttrs !== []) {
        foreach ($componentAttrs[0]->newInstance()->components as $component) {
            $addEdge($viewId, 'component:' . $component->value, 'uses');
        }
    }

    // #[ReceivesViewModel(ErrorViewModel::class)] → receives edges
    $viewModelAttrs = $ref->getAttributes(ReceivesViewModel::class);
    if ($viewModelAttrs !== []) {
        foreach ($viewModelAttrs[0]->newInstance()->viewModels as $vmClass) {
            $shortName = substr(strrchr($vmClass, '\\') ?: ('\\' . $vmClass), 1);
            $addNode('viewmodel:' . $shortName, 'viewmodel', $shortName);
            $addEdge($viewId, 'viewmodel:' . $shortName, 'receives');
        }
    }
}
```

### 3. Replace component template walking with Component enum attribute reflection

**Remove** (lines 263-279): the loop that reads each `templates/components/*.blade.php` and calls `parseComponentProps()`.

**Replace with** reflection on Component enum cases using `#[Prop]`:

```php
// Walk each Component enum case — props come from #[Prop] attributes, not @props().
foreach (Component::cases() as $Component) {
    $componentId = 'component:' . $Component->value;
    $addNode($componentId, 'component', $Component->value);

    $ref = new ReflectionEnumUnitCase(Component::class, $Component->name);
    $propAttrs = $ref->getAttributes(Prop::class, ReflectionAttribute::IS_INSTANCEOF);
    $props = [];
    foreach ($propAttrs as $attr) {
        $prop = $attr->newInstance();
        $enumShort = $prop->enum !== null
            ? substr(strrchr($prop->enum, '\\') ?: ('\\' . $prop->enum), 1)
            : null;
        $props[] = ['name' => $prop->name, 'default' => $prop->default, 'enum' => $enumShort];

        // If prop has an enum, wire the uses_enum edge
        if ($enumShort !== null) {
            $addNode('ui_enum:' . $enumShort, 'ui_enum', $enumShort);
            $addEdge($componentId, 'ui_enum:' . $enumShort, 'uses_enum');
        }
    }
    $nodes[$componentId]['props'] = $props;
}
```

### 4. Replace test file scanning with test class attribute reflection

**Remove** (lines 489-511): the loop that reads each `tests/Integration/*Test.php` and scans for `Route::*` via regex.

**Replace with** reflection on test classes using `#[CoversRoute]`:

```php
// Walk each integration test — coverage comes from #[CoversRoute], not regex.
foreach (glob($projectRoot . 'tests/Integration/*Test.php') ?: [] as $testFile) {
    $className = basename($testFile, '.php');
    $fqcn = 'ZeroToProd\\Thryds\\Tests\\Integration\\' . $className;
    if (!class_exists($fqcn)) {
        continue;
    }

    $testId = 'test:' . $className;
    $addNode($testId, 'test', $className);

    $ref = new ReflectionClass($fqcn);

    // #[CoversRoute(Route::home)] → covers edges
    $coversAttrs = $ref->getAttributes(CoversRoute::class);
    if ($coversAttrs !== []) {
        foreach ($coversAttrs[0]->newInstance()->routes as $route) {
            $addEdge($testId, 'route:' . $route->name, 'covers');
        }
    }

    // Convention: if test name matches a controller, wire that edge too
    $bare = substr($className, 0, -4); // remove 'Test'
    if (in_array($bare, $explicitControllers, true)) {
        $addEdge($testId, 'controller:' . $bare, 'covers');
    }
}
```

### 5. Remove UI enum scanning from template parsing

The current code discovers UI enums via `use ...UI\*` regex in templates. After this change, UI enum nodes are discovered via `#[Prop(enum: ButtonVariant::class)]` on Component enum cases (see step 3 above — the `uses_enum` edge is wired when a Prop has a non-null enum).

### 6. Add new `use` imports to inventory.php

```php
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\PageTitle;
use ZeroToProd\Thryds\Attributes\Prop;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\UsesComponent;
```

## Removed Code Summary

| Lines (approx) | What | Replacement |
|---|---|---|
| 60-92 | `parseComponentProps()` | `#[Prop]` reflection on Component cases |
| 99-128 | `parseTemplate()` | `#[ExtendsLayout]`, `#[UsesComponent]`, `#[ReceivesViewModel]` reflection on View cases |
| 222-260 | View template walking loop | View enum reflection loop |
| 263-279 | Component template walking loop | Component enum reflection loop |
| 489-511 | Test file regex scanning | Test class `#[CoversRoute]` reflection |

Total: ~130 lines of regex parsing removed, replaced with ~60 lines of attribute reflection.

## File Checklist

| File | Action |
|---|---|
| `scripts/inventory.php` | Remove regex functions, replace with attribute reflection |

## Verification

```bash
# Capture current inventory output for comparison
./run list:inventory > /tmp/inventory-before.json

# Apply changes to inventory.php

# Capture new inventory output
./run list:inventory > /tmp/inventory-after.json

# Diff the two — the graph structure (nodes, edges) should be identical
# Props format, descriptions, and sources should match
diff <(jq '.nodes | sort_by(.id)' /tmp/inventory-before.json) \
     <(jq '.nodes | sort_by(.id)' /tmp/inventory-after.json)

diff <(jq '.edges | sort_by(.from, .to, .rel)' /tmp/inventory-before.json) \
     <(jq '.edges | sort_by(.from, .to, .rel)' /tmp/inventory-after.json)

# Full check suite still green
./run check:all
```

The graph before and after must be structurally identical. Any difference means an attribute in Phase 2 was applied incorrectly — fix the attribute, not the inventory code.

## Notes

- The `decorateNode()` function (lines 320-485) is **not** changed in this phase. It adds descriptions, source paths, and `missing` flags. These still use reflection and file existence checks — no regex.
- The `$explicitControllers` array is still hardcoded. This will be replaced by `#[HandlesRoute]` reflection in a future phase (outside this migration scope — it's already in progress as an untracked file).
- The DOT output format is unaffected — it reads the same `$nodes` and `$edges` arrays.
- `extension_guides` extraction from `#[ClosedSet]` attributes is unaffected.
