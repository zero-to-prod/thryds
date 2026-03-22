# CLAUDE.md

Thryds — social media platform integrating AI with humanity. PHP 8.5, FrankenPHP, Docker.

## Rules

- ALL implementations MUST conform to **Attribute Oriented Programming (AOP)** and **Declarative Programming (DP)**.
- ALWAYS use `./run` or Docker commands. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run check:all` before completing any task.
- ALL code MUST be least invasive and straightforward, optimized for AOP/DP and an agentic experience.
- ALL code comments MUST be evergreen and not bound to a specific implementation.
- ALL entity names must be a lossless, non-misleading abstraction of its actual behavior and usage.
    - Flag if the name is broader, narrower, or orthogonal to observed responsibilities

## Organizing Principles

- Constants name things. Enumerations define sets. PHP Attributes define properties.
- `thryds.yaml` declares desired state. The attribute graph reflects actual state. Drift = failure.

## Orientation

The attribute graph is the primary way to understand the codebase. Query it first when orienting.

```
./run list:attributes                          # full graph, YAML (default)
./run list:attributes -- --format=json         # full graph, JSON
./run list:attributes -- --format=markdown     # readable document
./run list:attributes -- --format=mermaid      # class diagram (Mermaid)
./run list:attributes -- --format=dot          # class diagram (Graphviz DOT)
./run list:attributes -- --format=png --output=var/graph.png  # rendered DOT diagram (Kroki API)
./run list:attributes -- --output=var/graph.yaml
```

### Filters (combinable — AND across types, OR within same type)

```
--node=<ShortName>   # node + one-hop neighbors
--layer=<layer>      # semantic layer (core, views, controllers, etc.)
--kind=<kind>        # class, enum, interface, trait
--attr=<Attribute>   # nodes carrying a specific attribute
--rel=<rel>          # edges of a specific relationship type
--file=<substring>   # nodes whose file path contains substring
--sections=<list>    # comma-separated top-level keys (yaml/json/markdown only)
                     # valid: _index, _instructions, _dependents, edges, nodes
--compact            # strip edge args/file paths; aggregate dependents by count
```

### Output keys (YAML/JSON)

| Key             | Content                                                                  |
|-----------------|--------------------------------------------------------------------------|
| `_index`        | Layer → sorted short class names                                         |
| `_instructions` | Mutation recipes (addCase/addKey) from attributes                        |
| `_dependents`   | Reverse-edge index: target → dependents                                  |
| `edges`         | Directed: from, to, rel, kind, source, args, file paths                  |
| `nodes`         | Keyed by FQCN: file, kind, layer, attributes, properties, methods, cases |

### Agent workflow with the graph

1. **Orient** — `--compact --sections=_index,_instructions,_dependents` for the full map without node detail.
2. **Focus** — `--node=X` for a node and its neighbors.
3. **Query** — combine `--layer`, `--attr`, `--kind`, `--rel` for targeted questions.
4. **Mutate** — read `_instructions` for mutation recipes.
5. **Impact** — read `_dependents` to know what breaks if a node changes.

Configuration: `attribute-graph.yaml` at project root externalizes class references for `scripts/list-attributes.php`.

## Workflow

1. Read `thryds.yaml` to understand the project.
2. Edit `thryds.yaml` to declare new entities.
3. `./run sync:manifest` to scaffold code with correct attributes.
4. Implement business logic in generated stubs.
5. `./run fix:all` (includes `check:manifest` — fails if drift remains).

### Manifest enforcement

- `check:manifest` runs inside `check:all` — every task completion.
- `sync:manifest` runs inside `fix:all` — every fix cycle.
- Drift categories: `missing_from_code`, `missing_from_manifest`, `property_drift`.
- Output is structured JSON.

## Commands

`./run <script>` executes `docker compose exec web composer <script>`.
`./run composer <cmd>` passes through to Composer (update, require, etc.). Raw PHP: `docker compose exec web php scripts/<name>.php`.

### Check (read-only)

```
check:all              # PRIMARY — all checks + tests; JSON summary, non-aborting
check:manifest         # diff thryds.yaml against attribute graph
check:composer         # validate composer.json integrity
check:style            # php-cs-fixer --dry-run --diff
check:rector           # rector --dry-run
check:types            # phpstan analyse
check:migrations       # migration file integrity
check:requirements     # requirement → code coverage trace
check:blade-routes     # lint route references in Blade
check:blade-components # lint component usage in Blade
check:blade-templates  # lint Blade template structure
check:blade-push       # verify @push stacks consumed by @stack
check:coverage         # PCOV; metrics + clover XML → var/coverage/; pass -- <N> for threshold
check:preload          # verify preload manifest is current
check:graph            # validate attribute graph integrity
```

### Test (read-only)

```
test                   # full suite (unit + integration + database)
test:unit              # unit tests only
test:integration       # integration tests
test:database          # database tests (isolated transactions)
test:rector            # custom Rector rule tests
test:load              # k6 load test (production build; uses compose.load-test.yaml)
```

### Inspect (read-only)

```
list:commands          # print all available commands
list:routes            # → JSON [{name, path, params, dev_only, description, operations}]
list:attributes        # attribute graph (see Orientation section)
list:inventory         # dependency graph: routes→controllers→views→components; JSON or DOT (-- --format=dot)
list:manifest          # inventory in YAML format
migrate:status         # → stderr display; → stdout JSON {passed, pending, modified, applied}
db:query -- "<sql>"    # SELECT only → JSON rows
```

### Audit (read-only)

```
audit:production       # route cache + OPcache + template cache + @push readiness
audit:route-cache      # route cache only
audit:opcache          # OPcache configuration audit
audit:profile          # profile live endpoints (makes HTTP requests)
audit:hotspots         # access log analysis
```

### Fix (mutating)

```
fix:all                # sync:manifest → fix:style → fix:rector → sync:preload → check:all
                       # pass --dry-run (-n) to preview without mutating
fix:style              # php-cs-fixer fix
fix:rector             # rector process
```

### Sync (mutating — derived outputs from current state)

```
sync:manifest          # scaffold code for entities in thryds.yaml missing from code
sync:schema            # create missing tables; sync #[Column] attrs → JSON {created,synced,flagged_missing_from_model,flagged_missing_from_db,no_changes}
sync:schema -- --dry-run  # report drift without modifying
sync:views             # compile Blade templates → var/cache/blade/
sync:preload           # regenerate preload.php for OPcache
```

### Migrate (mutating)

```
migrate                # apply pending → JSON {applied:[…], total}
migrate:rollback       # undo last → JSON {rolled_back:{id,description}|null}
```

### Generate (mutating — scaffold new files)

```
generate:migration -- <PascalCaseClassName>
  # → migrations/NNNN_<ClassName>.php

generate:requirement -- <ID> --type=functional|non-functional --verification=integration-test|unit-test|rector-rule|architecture|manual [--title="..."]
  # → appends to requirements.yaml + test stub

generate:rector-rule -- <RuleName> [--mode=auto|warn] [--message="..."]
  # → rule, test, config, fixture, docs → appended to rector.php

generate:table -- <table_name> [--force]
  # → src/Tables/<PascalCase>Table.php from live schema
```

### Environment (mutating)

```
env:dev                # APP_ENV=development, restart with dev overlay
env:prod               # APP_ENV=production, restart without dev overlay
env:up                 # start dev containers (preserves .env)
```

## Environment Files

| File                       | Purpose                                                             |
|----------------------------|---------------------------------------------------------------------|
| `compose.yaml`             | Base (dev + prod). Always loaded.                                   |
| `compose.development.yaml` | Dev overrides — hot reload, file-watching worker. Never production. |
| `compose.load-test.yaml`   | Production load test target.                                        |

## Logs

```
logs/frankenphp/access.log   # method, URI, status, duration, request ID
logs/frankenphp/caddy.log    # worker restarts, file watches
logs/php/error.log           # PHP errors, warnings, deprecations
```

## JetBrains MCP Tools

| Tool                         | Purpose                                        |
|------------------------------|------------------------------------------------|
| `execute_terminal_command`   | Run shell command in IDE terminal              |
| `get_file_text_by_path`      | Read file contents by project-relative path    |
| `replace_text_in_file`       | Find-and-replace text in a file                |
| `create_new_file`            | Create a new file (auto-creates parent dirs)   |
| `open_file_in_editor`        | Open file in IDE editor                        |
| `reformat_file`              | Apply IDE code formatting                      |
| `rename_refactoring`         | Rename symbol across project (structure-aware) |
| `get_file_problems`          | Inspect file for errors/warnings               |
| `get_symbol_info`            | Quick documentation for symbol at position     |
| `search_in_files_by_text`    | Substring search across project                |
| `search_in_files_by_regex`   | Regex search across project                    |
| `find_files_by_name_keyword` | Find files by name substring (fast, indexed)   |
| `find_files_by_glob`         | Find files by glob pattern (recursive)         |
| `list_directory_tree`        | Tree view of a directory                       |
| `get_all_open_file_paths`    | List open editor tabs                          |
| `get_run_configurations`     | List IDE run configurations                    |
| `execute_run_configuration`  | Run a named run configuration                  |
| `get_project_dependencies`   | List project library dependencies              |
| `get_project_modules`        | List project modules and types                 |
| `get_repositories`           | List VCS roots in project                      |
