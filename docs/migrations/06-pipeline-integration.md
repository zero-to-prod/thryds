# Phase 6: Pipeline Integration

## Objective

Wire `check:manifest` into `check:all` and `sync:manifest` into `fix:all`. Update `CLAUDE.md` to document the new commands and the manifest-first workflow. After this phase, the enforcement loop runs automatically on every `./run check:all` and `./run fix:all`.

## Prerequisites

- Phase 5 complete: `check:manifest` and `sync:manifest` work standalone

## Deliverables

### 1. `scripts/check-all.php` — Add `check:manifest`

Insert `check:manifest` into the `$checks` array. Position it first — manifest drift is the most fundamental check and the most useful to see early.

Current `$checks` array (line 23):
```php
$checks = [
    'check:composer'         => 'composer validate',
    'check:style'            => ...,
    // ...
];
```

Modified:
```php
$checks = [
    'check:manifest'         => 'php ' . escapeshellarg($base_dir . '/scripts/check-manifest.php'),
    'check:composer'         => 'composer validate',
    'check:style'            => $base_dir . '/vendor/bin/php-cs-fixer fix --dry-run --diff',
    'check:rector'           => $base_dir . '/vendor/bin/rector process --dry-run',
    'check:types'            => $base_dir . '/vendor/bin/phpstan analyse',
    'check:migrations'       => 'php ' . escapeshellarg($base_dir . '/scripts/check-migrations.php'),
    'check:requirements'     => 'php ' . escapeshellarg($base_dir . '/scripts/check-requirement-coverage.php'),
    'check:blade-routes'     => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-routes.php'),
    'check:blade-components' => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-components.php'),
    'check:blade-templates'  => 'php ' . escapeshellarg($base_dir . '/scripts/lint-blade-templates.php'),
    'check:blade-push'       => 'php ' . escapeshellarg($base_dir . '/scripts/check-blade-push.php'),
    'test'                   => $base_dir . '/vendor/bin/phpunit',
];
```

Add fix suggestion:
```php
$fixes = [
    'check:manifest' => './run sync:manifest',
    'check:style'    => './run fix:style',
    'check:rector'   => './run fix:rector',
];
```

### 2. `fix:all` Pipeline — Add `sync:manifest`

The `fix:all` composer script currently runs (per CLAUDE.md):
```
generate:routes → fix:style → fix:rector → generate:preload → check:all
```

Updated pipeline:
```
sync:manifest → generate:routes → fix:style → fix:rector → generate:preload → check:all
```

`sync:manifest` runs first because it may create new files (Route cases, View cases, controllers, templates) that `generate:routes` and `fix:style` need to process.

Update `composer.json` `fix:all` script:
```json
"fix:all": [
    "@sync:manifest",
    "@generate:routes",
    "@fix:style",
    "@fix:rector",
    "@generate:preload",
    "@check:all"
]
```

### 3. `CLAUDE.md` — Document New Commands and Workflow

Add to the **Read-only Commands / Check** section:
```
./run check:manifest      # diff thryds.yaml against attribute graph
```

Add to the **Read-only Commands / Inspect** section:
```
./run list:manifest       # generate thryds.yaml-format YAML from attributes
```

Add to the **Mutating Commands / Fix** section, update `fix:all`:
```
./run fix:all             # sync:manifest → generate:routes → fix:style → fix:rector → generate:preload → check:all
./run sync:manifest       # scaffold code for entities in thryds.yaml missing from code
```

Add new section **Manifest** after **Organizing Principles**:

```markdown
## Manifest

`thryds.yaml` at the project root declares the desired project structure. Every value maps to a PHP attribute. The attribute graph (read via reflection by `list:inventory`) is the actual state.

### Workflow
1. Read `thryds.yaml` to understand the project
2. Edit `thryds.yaml` to declare new entities
3. Run `./run sync:manifest` to scaffold code with correct attributes
4. Implement business logic in generated stubs
5. Run `./run fix:all` (includes `check:manifest` — fails if drift remains)

### Enforcement
- `check:manifest` is part of `check:all` — runs on every task completion
- `sync:manifest` is part of `fix:all` — runs on every fix cycle
- Drift categories: `missing_from_code`, `missing_from_manifest`, `property_drift`
- Output is structured JSON — agents parse it directly
```

### 4. Update Inventory Extension Guides

The `extension_guides` output from `list:inventory` should reference the manifest workflow:

Update the route extension guide (from `#[ClosedSet]` on Route enum):
```
1. Add entry to thryds.yaml routes section.
2. Run ./run sync:manifest.
3. Implement controller logic (if controller route).
4. Run ./run fix:all.
```

Update similar guides for view, component, model, viewmodel, controller.

These guides are read from `#[ClosedSet(addCase: '...')]` attribute values, so the attribute string constants need updating.

Files to update:
- `src/Routes/Route.php` — `#[ClosedSet(addCase: '...')]`
- `src/Blade/View.php` — `#[ClosedSet(addCase: '...')]`
- `src/Blade/Component.php` — `#[ClosedSet(addCase: '...')]`
- `src/Attributes/Table.php` — `Table::addCase` constant
- `src/Attributes/ViewModel.php` — `ViewModel::addCase` constant
- `scripts/inventory.php` — hardcoded controller extension guide string

## File Checklist

| File | Action |
|---|---|
| `scripts/check-all.php` | Add `check:manifest` as first check, add fix suggestion |
| `composer.json` | Update `fix:all` to prepend `@sync:manifest`; add `list:manifest` script if not added in Phase 4 |
| `CLAUDE.md` | Add manifest section, update command reference |
| `src/Routes/Route.php` | Update `#[ClosedSet(addCase:)]` to reference manifest workflow |
| `src/Blade/View.php` | Update `#[ClosedSet(addCase:)]` to reference manifest workflow |
| `src/Blade/Component.php` | Update `#[ClosedSet(addCase:)]` to reference manifest workflow |
| `src/Attributes/Table.php` | Update `addCase` constant |
| `src/Attributes/ViewModel.php` | Update `addCase` constant |
| `scripts/inventory.php` | Update controller extension guide string |

## Verification

```bash
# fix:all now includes sync:manifest
./run fix:all
# Output should show sync:manifest running first, then the rest

# check:all now includes check:manifest as first check
./run check:all
# Output should show check:manifest as [ OK ] first

# Introduce intentional drift: add a dummy route to thryds.yaml
# Then run check:all — should show check:manifest as [FAIL]
# Then run fix:all — sync:manifest scaffolds, check:manifest passes

# Full pipeline green
./run check:all
```

## End State

After this phase:

1. **Every task completion** runs `check:all` which includes `check:manifest` — any drift between `thryds.yaml` and the attribute graph is caught immediately
2. **Every fix cycle** runs `fix:all` which includes `sync:manifest` — missing code is scaffolded automatically
3. **CLAUDE.md** documents the manifest-first workflow — agents read it and know to edit `thryds.yaml` first
4. **Extension guides** in inventory output reference the manifest workflow — agents scaffolding new entities follow the right process

The enforcement loop is:
```
Agent reads thryds.yaml
  → Agent edits thryds.yaml
    → Agent runs fix:all
      → sync:manifest scaffolds missing code
        → generate:routes rebuilds enums
          → fix:style + fix:rector clean up
            → generate:preload updates OPcache
              → check:all validates everything
                → check:manifest confirms zero drift
```
