# Phase 4: YAML Manifest Schema and Initial Manifest

## Objective

Define the `thryds.yaml` schema, add a YAML output format to inventory, and generate the initial manifest from the current codebase. After this phase, `thryds.yaml` exists at the project root and `./run list:inventory -- --format=yaml` produces identical output.

## Prerequisites

- Phase 3 complete: inventory derives the full graph from attributes only

## Deliverables

### 1. `thryds.yaml` — The Manifest

The manifest is a YAML file at the project root. It is committed to the repository. It declares the desired project structure — every entity, every property, every relationship.

The manifest is organized by entity type. Within each section, entries are keyed by the entity's canonical name (enum case name, class short name). Properties map 1:1 to PHP attributes.

```yaml
# thryds.yaml — project structure manifest
# Enforcement: ./run check:manifest (diff against attribute graph)
# Sync: ./run sync:manifest (scaffold missing code)

routes:

  about:
    path: /about
    description: About
    dev_only: false
    operations:
      GET: Company and product information
    view: about

  home:
    path: /
    description: Home
    dev_only: false
    operations:
      GET: Marketing home page
    controller: HomeController
    view: home

  login:
    path: /login
    description: Login
    dev_only: false
    operations:
      GET: User authentication form
    view: login

  opcache_scripts:
    path: /_opcache/scripts
    description: OPcache scripts
    dev_only: true
    operations:
      GET: Scripts loaded in OPcache

  opcache_status:
    path: /_opcache/status
    description: OPcache status
    dev_only: true
    operations:
      GET: OPcache runtime statistics

  register:
    path: /register
    description: Register
    dev_only: false
    operations:
      GET: New user registration form
      POST: Handle registration submission
    controller: RegisterController
    view: register

  routes:
    path: /_routes
    description: Routes
    dev_only: true
    operations:
      GET: Machine-readable manifest of all registered routes

  styleguide:
    path: /_styleguide
    description: Styleguide
    dev_only: true
    operations:
      GET: UI component and design token reference
    view: styleguide


controllers:

  HomeController:
    route: home
    operations:
      GET: Marketing home page
    renders: home
    persists: []
    redirects_to: []

  RegisterController:
    route: register
    operations:
      GET: New user registration form
      POST: Handle registration submission
    renders: register
    persists: [User]
    redirects_to: [login]


views:

  about:
    layout: base
    title: About — Thryds
    components: [card]
    viewmodels: []

  error:
    layout: base
    title: Error — Thryds
    components: [alert, card]
    viewmodels: [ErrorViewModel]

  home:
    layout: base
    title: Thryds
    components: [card, button]
    viewmodels: []

  login:
    layout: base
    title: Login — Thryds
    components: [card, form_group, input, button]
    viewmodels: []

  register:
    layout: base
    title: Register — Thryds
    components: [card, form_group, input, button]
    viewmodels: []

  styleguide:
    layout: base
    title: Styleguide — Thryds
    components: [alert, button, card, form_group, input]
    viewmodels: []


components:

  alert:
    description: Inline status banner for feedback messages (info, danger, success).
    props:
      variant: { default: info, enum: AlertVariant }

  button:
    description: Action trigger with configurable visual variant and size.
    props:
      variant: { default: primary, enum: ButtonVariant }
      size:    { default: md, enum: ButtonSize }
      type:    { default: button }

  card:
    description: Contained surface for grouping related content.
    props: {}

  form_group:
    description: Label + input wrapper that enforces consistent form field layout.
    props:
      label: {}

  input:
    description: Text field bound to a typed HTML input type.
    props:
      type: { default: text, enum: InputType }


viewmodels:

  ErrorViewModel:
    view_key: ErrorViewModel
    fields:
      message: string
      status_code: int


enums:

  AlertVariant:
    cases: [info, danger, success]

  ButtonSize:
    cases: [sm, md, lg]

  ButtonVariant:
    cases: [primary, danger, secondary]

  InputType:
    cases: [text, email, password]


tables:

  User:
    table: users
    engine: InnoDB
    primary_key: [id]
    indexes: []
    columns:
      id:                { type: CHAR, length: 26, comment: Primary key }
      name:              { type: VARCHAR, length: 255, comment: Display name }
      handle:            { type: VARCHAR, length: 30, comment: Unique public username }
      email:             { type: VARCHAR, length: 255, nullable: true, comment: Contact email address }
      email_verified_at: { type: TIMESTAMP, nullable: true, comment: Timestamp of email verification }
      password:          { type: VARCHAR, length: 255, comment: Hashed password }
      created_at:        { type: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record creation time }
      updated_at:        { type: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record last update time }


tests:

  AboutRouteTest:
    type: integration
    covers_routes: [about]

  BladeCacheTest:
    type: integration
    covers_routes: []

  HOT004Test:
    type: integration
    covers_routes: []

  HomeControllerTest:
    type: integration
    covers_routes: [home]

  LoginRouteTest:
    type: integration
    covers_routes: [login]

  OpcacheScriptsRouteTest:
    type: integration
    covers_routes: [opcache_scripts]

  OpcacheStatusRouteTest:
    type: integration
    covers_routes: [opcache_status]

  RegisterRouteTest:
    type: integration
    covers_routes: [register]

  RoutesRouteTest:
    type: integration
    covers_routes: [routes]

  StyleguideRouteTest:
    type: integration
    covers_routes: [styleguide]

  TRACE001Test:
    type: integration
    covers_routes: []
```

### 2. YAML Output Format for Inventory

Extend `scripts/inventory.php` to support `--format=yaml`. This produces YAML from the attribute graph using the same schema as `thryds.yaml`.

Add to the format parser (around line 30):
```php
$format = 'json';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    }
}
// Validate format
if (!in_array($format, ['json', 'dot', 'yaml'], true)) {
    fwrite(STDERR, "Unknown format: $format. Use json, dot, or yaml.\n");
    exit(1);
}
```

Add YAML output block after the existing JSON/DOT blocks:

```php
if ($format === 'yaml') {
    // Transform the flat node/edge graph into the grouped manifest schema.
    // This function reads $decoratedNodes and $edges and outputs YAML
    // matching the thryds.yaml schema exactly.
    echo buildYamlManifest($decoratedNodes, $edges);
}
```

The `buildYamlManifest()` function transforms the flat node/edge arrays into the grouped YAML structure. It must:

1. Group nodes by type (route, controller, view, component, viewmodel, ui_enum → enum, model → table, test)
2. For each node, emit its attribute-derived properties
3. For relationship properties (route.controller, route.view, view.components, view.viewmodels, controller.persists, etc.), resolve them from the edges array
4. Emit columns in compact format (only non-default values)
5. Sort entries alphabetically within each section

**YAML generation:** Use `symfony/yaml` (`Yaml::dump()`) which is already a dev dependency. Set inline level to control formatting — nested arrays should inline for compactness.

### 3. Composer Script

Add to `composer.json` scripts:
```json
"list:manifest": "php scripts/inventory.php --format=yaml"
```

### 4. Schema Documentation

The manifest schema is implicitly defined by the YAML structure. For tooling support (IDE autocomplete, YAML validation), create a JSON Schema file:

`thryds.schema.json` — JSON Schema defining the structure of `thryds.yaml`. This enables the `yaml-language-server` directive at the top of the manifest.

Key schema rules:
- `routes.*` entries require `path`, `description`, `operations` (map of method→description)
- `routes.*.dev_only` defaults to `false` (omit when false)
- `routes.*.controller` and `routes.*.view` are optional (not all routes have both)
- `controllers.*` entries require `route`, `operations`, `renders`
- `views.*` entries require `layout`, `title`, `components`, `viewmodels`
- `components.*` entries require `description`, `props`
- `tables.*.columns.*` only includes non-default values (nullable, unsigned, auto_increment, default, precision, scale, values are omitted when they match Column attribute defaults)

## File Checklist

| File | Action |
|---|---|
| `thryds.yaml` | Create at project root |
| `scripts/inventory.php` | Add `--format=yaml` output + `buildYamlManifest()` function |
| `composer.json` | Add `list:manifest` script |
| `thryds.schema.json` | Create JSON Schema for manifest validation |

## Verification

```bash
# Generate YAML from inventory and compare to thryds.yaml
./run list:manifest > /tmp/manifest-from-inventory.yaml
diff thryds.yaml /tmp/manifest-from-inventory.yaml

# The diff should be empty — the committed manifest matches the attribute graph exactly.
# If there are differences, either the manifest or the attributes are wrong.

# Composer script works
./run list:manifest

# Full suite still green
./run check:all
```

## Schema Defaults (Compact Column Format)

Columns omit these values when they match the `#[Column]` attribute defaults:

| Field | Default (omit when) |
|---|---|
| `nullable` | `false` |
| `unsigned` | `false` |
| `auto_increment` | `false` |
| `default` | `null` |
| `precision` | `null` |
| `scale` | `null` |
| `values` | `null` |
| `length` | `null` (but always present for VARCHAR/CHAR) |

This keeps column declarations to a single line in most cases.
