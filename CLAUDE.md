# CLAUDE.md

## Project

Thryds (a play on words from _threads_) — social media platform integrating AI with humanity. PHP 8.5, FrankenPHP, Docker.

## Rules

This project conforms and enforces the following rules **Attribute Oriented Programming (AOP)** style and **Declarative Programming (DP)** style.

- ALL implementations MUST conform to the **AOP** and **DP** principles.
- ALWAYS `./run` or Docker commands. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run check:all` before completing any task.
- ALL code implementations MUST be least invasive and straightforward, optimized for AOP/DP and an agentic experience.
- ALL code comments MUST be evergreen and not bound to a specific implementation.

## Invocation

```
./run <script>          # docker compose exec web composer <script>
./run composer <cmd>    # pass-through: update, require, etc.
./run test:load         # docker compose -f compose.load-test.yaml run --rm k6
./run dev               # APP_ENV=development, restart with dev overlay
./run prod              # APP_ENV=production, restart without dev overlay
./run dev:up            # start dev containers (preserves .env)
```

Raw PHP: `docker compose exec web php scripts/<name>.php`

## Environment

| File                       | Purpose                                                             |
|----------------------------|---------------------------------------------------------------------|
| `compose.yaml`             | Base (dev + prod). Always loaded.                                   |
| `compose.development.yaml` | Dev overrides — hot reload, file-watching worker. Never production. |
| `compose.load-test.yaml`   | Production load test target.                                        |

## Read-only Commands

No side effects — safe to run anytime.

### Check

```
./run check:all           # PRIMARY — all checks + tests; JSON summary, non-aborting
./run check:manifest      # diff thryds.yaml against attribute graph
./run check:composer      # validate composer.json integrity
./run check:style         # php-cs-fixer --dry-run --diff
./run check:rector        # rector --dry-run
./run check:types         # phpstan analyse
./run check:migrations    # migration file integrity
./run check:requirements  # requirement → code coverage trace
./run check:blade-routes
./run check:blade-components
./run check:blade-templates
./run check:blade-push
./run check:coverage      # PCOV; metrics + clover XML → var/coverage/; pass -- <N> for line threshold
```

### Test

```
./run test              # full suite (unit + integration + database)
./run test:unit
./run test:integration
./run test:database
./run test:rector       # custom Rector rule tests
./run test:coverage     # alias for check:coverage
./run test:load         # k6 load test (production build)
```

### Inspect

```
./run list:routes         # → JSON [{name, path, params, dev_only, description, operations}]
./run list:attributes     # attribute graph — YAML (default), JSON, Markdown, or Mermaid
./run migrate:status      # → stderr (display); → stdout JSON {passed, pending, modified, applied}
./run db:query -- "<sql>" # SELECT only → JSON rows
```

### Audit

```
./run prod:check        # route cache + OPcache + template cache + @push readiness
./run prod:route-cache  # route cache only
./run audit:opcache     # OPcache configuration audit
./run audit:profile     # profile live endpoints (makes HTTP requests)
./run audit:hotspots    # access log analysis
```

## Mutating Commands

### Fix

```
./run fix:all           # sync:manifest → fix:style → fix:rector → generate:preload → check:all
./run sync:manifest     # scaffold code for entities in thryds.yaml missing from code
./run fix:style         # php-cs-fixer fix
./run fix:rector        # rector process
```

### Migrate & Cache

```
./run migrate           # apply pending → JSON {applied:[{id,description}…], total}
./run migrate:rollback  # undo last → JSON {rolled_back:{id,description}|null}
./run sync:schema       # create missing tables; sync #[Column] attrs to live schema → JSON {created,synced,flagged_missing_from_model,flagged_missing_from_db,no_changes}
./run sync:schema -- --dry-run  # report drift without modifying anything
./run cache:views       # compile Blade templates → var/cache/blade/
```

### Scaffold

```
./run generate:migration -- <PascalCaseClassName>
  # → migrations/NNNN_<ClassName>.php

./run generate:requirement -- <ID> --type=functional|non-functional --verification=integration-test|unit-test|rector-rule|architecture|manual [--title="..."]
  # → appends to requirements.yaml
  # → tests/Integration/<IDnodash>Test.php  (if integration-test)
  # → tests/Unit/<IDnodash>Test.php         (if unit-test)

./run generate:rector-rule -- <RuleName> [--mode=auto|warn] [--message="..."]
  # → utils/rector/src/<RuleName>.php
  # → utils/rector/tests/<RuleName>/<RuleName>Test.php
  # → utils/rector/tests/<RuleName>/config/configured_rule.php
  # → utils/rector/tests/<RuleName>/Fixture/example.php.inc
  # → utils/rector/docs/<RuleName>.md
  # → appended to rector.php

./run generate:table -- <table_name> [--force]
  # → src/Tables/<PascalCase>Table.php (generated from live schema)

./run generate:preload  # regenerate preload.php for OPcache (run before prod:check)
```

## Logs

```
logs/frankenphp/access.log   # method, URI, status, duration, request ID
logs/frankenphp/caddy.log    # worker restarts, file watches
logs/php/error.log           # PHP errors, warnings, deprecations
```

## Organizing Principles

- Constants name things
- Enumerations define sets
- PHP Attributes define properties

## Manifest

`thryds.yaml` at the project root declares the desired project structure. Every value maps to a PHP attribute. The attribute graph (read via reflection by `scripts/attribute-graph.php`) is the actual state.

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

## Attribute Graph (`scripts/attribute-graph.php`)

The attribute graph is the primary way to understand the codebase. It reflects every PHP attribute across all classes, enums, interfaces, and traits into a structured graph of nodes and edges. AI agents should query it first when orienting on unfamiliar code.

### Invocation

```
./run list:attributes                                          # full graph, YAML
./run list:attributes -- --format=json                         # full graph, JSON
./run list:attributes -- --format=markdown                     # full graph, readable document
./run list:attributes -- --format=mermaid                      # full graph, class diagram
./run list:attributes -- --format=yaml --output=var/graph.yaml # write to file
```

Raw: `docker compose exec web php scripts/attribute-graph.php [options]`

### Filters (combinable — AND across types, OR within same type)

```
--node=<ShortName>   # include node + its one-hop neighbors via edges
--layer=<layer>      # filter by semantic layer (core, views, controllers, etc.)
--kind=<kind>        # filter by kind (class, enum, interface, trait)
--attr=<Attribute>   # filter to nodes carrying a specific attribute name
--rel=<rel>          # filter edges to specific relationship types
--file=<substring>   # filter to nodes whose file path contains substring
```

### Examples

```
# Explore a single controller and everything it touches
./run list:attributes -- --node=RegisterController

# All view models
./run list:attributes -- --layer=viewmodels

# Every class carrying #[Table]
./run list:attributes -- --attr=Table

# Enums only, as Markdown
./run list:attributes -- --kind=enum --format=markdown

# Edges of a specific relationship type
./run list:attributes -- --rel=receivesviewmodel
```

### Output structure (YAML/JSON)

| Key              | Content                                                        |
|------------------|----------------------------------------------------------------|
| `_index`         | Layer → sorted list of short class names                       |
| `_instructions`  | Mutation instructions (addCase/addKey) extracted from attributes |
| `_dependents`    | Reverse-edge index: target node → list of dependents           |
| `edges`          | Directed edges: from, to, rel, kind, source, args, file paths  |
| `nodes`          | Keyed by FQCN: file, kind, layer, attributes, properties, methods, cases |

### Configuration

`attribute-graph.yaml` at the project root externalizes all codebase-specific class references (ClosedSet, Group, EdgeKind, Layer) so the script is reusable across any AOP project.

### How an AI agent benefits

1. **Orient** — run `--format=yaml` (or `--format=json`) unfiltered to get the full graph; parse `_index` to see what layers and nodes exist.
2. **Focus** — use `--node=X` to pull a single node and its neighbors; read the edges to understand how it connects.
3. **Query** — combine `--layer`, `--attr`, `--kind`, and `--rel` to answer targeted questions ("which controllers use #[ReceivesViewModel]?").
4. **Follow instructions** — read `_instructions` for mutation recipes (e.g., how to add a new enum case or map key).
5. **Trace dependents** — read `_dependents` to know what breaks if a node changes.

## JetBrains MCP Tools

| Tool | Purpose |
|------|---------|
| `execute_terminal_command` | Run shell command in IDE terminal |
| `get_file_text_by_path` | Read file contents by project-relative path |
| `replace_text_in_file` | Find-and-replace text in a file |
| `create_new_file` | Create a new file (auto-creates parent dirs) |
| `open_file_in_editor` | Open file in IDE editor |
| `reformat_file` | Apply IDE code formatting |
| `rename_refactoring` | Rename symbol across project (structure-aware) |
| `get_file_problems` | Inspect file for errors/warnings |
| `get_symbol_info` | Quick documentation for symbol at position |
| `search_in_files_by_text` | Substring search across project |
| `search_in_files_by_regex` | Regex search across project |
| `find_files_by_name_keyword` | Find files by name substring (fast, indexed) |
| `find_files_by_glob` | Find files by glob pattern (recursive) |
| `list_directory_tree` | Tree view of a directory |
| `get_all_open_file_paths` | List open editor tabs |
| `get_run_configurations` | List IDE run configurations |
| `execute_run_configuration` | Run a named run configuration |
| `get_project_dependencies` | List project library dependencies |
| `get_project_modules` | List project modules and types |
| `get_repositories` | List VCS roots in project |
