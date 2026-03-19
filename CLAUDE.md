# CLAUDE.md

## Project

Thryds — a play off of threads, is a social media platform integrating AI with humanity. Web UI + API backend. PHP 8.5, FrankenPHP, Docker.

## Rules

- ALWAYS use Docker. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run check:all` before completing any task.
- ALL code implementations MUST be least invasive and straightforward, optimized for an ai-native experience.
- ALL code comments MUST be evergreen and not bound to a specific implementation.

## Command Execution

```
./run list              # print all available commands with descriptions
./run <script>          # docker compose exec web composer <script>
./run composer <cmd>    # docker compose exec web composer <cmd>  (e.g. composer update, composer require …)
./run test:load         # docker compose -f compose.load-test.yaml run --rm k6
./run dev               # set APP_ENV=development, restart with dev overlay
./run prod              # set APP_ENV=production, restart without dev overlay
./run dev:up            # start dev (does not update .env)
```

Raw PHP: `docker compose exec web php scripts/<name>.php`

## Environment

| File | Purpose |
|---|---|
| `compose.yaml` | Base (dev + prod). Always loaded. |
| `compose.development.yaml` | Dev overrides — hot reload, file-watching worker. Never production. |
| `compose.load-test.yaml` | Production load test target. |

## Check & Test (read-only, no side effects)

```
./run check:all         # PRIMARY — all checks + tests, JSON summary, non-aborting
./run check:composer    # composer validate — integrity and consistency of composer.json
./run check:style       # php-cs-fixer --dry-run --diff
./run check:rector      # rector --dry-run
./run check:types       # phpstan
./run check:migrations  # migration file integrity
./run check:requirements
./run check:blade-routes
./run check:blade-components
./run check:blade-templates
./run check:blade-push
./run test              # phpunit (all suites)
./run test:unit
./run test:integration
./run test:database
./run test:rector       # custom Rector rule tests
./run test:coverage     # phpunit with PCOV; coverage metrics + clover XML → var/coverage/
./run test:load         # k6 load test (production build)
./run check:coverage    # same as test:coverage; pass -- <N> to enforce an N% line threshold
```

## Fix (modifies files)

```
./run fix:style         # php-cs-fixer fix
./run fix:rector        # rector process
```

## Scaffold (generates files)

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

./run generate:preload  # regenerates preload.php for OPcache (run before prod:check)
```

## Migrate & Cache

```
./run migrate           # apply pending migrations; JSON {"applied":[{id,description}…],"total":N}
./run migrate:status    # display → stderr; JSON {"passed":bool,"pending":[…],"modified":[…],"applied":[…]} → stdout
./run migrate:rollback  # roll back last migration; JSON {"rolled_back":{id,description}|null}
./run cache:views       # compile Blade templates to var/cache/blade/
```

## Production

```
./run prod:check        # route cache + OPcache + template cache + @push readiness
./run prod:route-cache  # route cache check only
./run audit:opcache     # OPcache configuration audit
./run audit:profile     # endpoint profiling (makes live HTTP requests)
./run audit:hotspots    # access log analysis (read-only)
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
