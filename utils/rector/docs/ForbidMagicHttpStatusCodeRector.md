# ForbidMagicHttpStatusCodeRector

Flags raw HTTP status code integers passed to response construction calls, requiring them to be replaced with named constants or enum cases.

**Category:** Forbidden Constructs
**Mode:** `warn`
**Auto-fix:** No

## Rationale

HTTP status codes are a closed set. When scattered as bare integers across controllers and response builders, any change — upgrading `200` to `201` for resource creation, adding a new error type, or auditing all 404 responses — requires hunting every call site. A named constant or enum case gives the value a canonical home, makes it greppable, and signals intent in the code. "Constants name things" applies to HTTP semantics just as it does to array keys.

## What It Detects

Magic integer literals that match known HTTP status codes when passed as arguments to:

- Helper functions: `response(...)` (configurable via `functionNames`)
- Method calls: `->withStatus(...)` (configurable via `methodNames`)
- Object construction: `new Response(...)`, `new JsonResponse(...)`, `new RedirectResponse(...)`, `new HtmlResponse(...)` (configurable via `newClassNames`)

Non-HTTP integers (e.g., `42`, `7`) are never flagged. Named constants, class constants, and enum case values are already safe — the rule only fires on raw integer literal nodes.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to the statement containing the offending call. The comment text is configurable via `message`, with `%d` replaced by the integer value.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `'TODO: Replace %d with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md'` | Comment template; `%d` is replaced with the status code |
| `functionNames` | `string[]` | `['response']` | Helper function names whose arguments are inspected |
| `methodNames` | `string[]` | `['withStatus']` | Method names whose arguments are inspected |
| `newClassNames` | `string[]` | `['Response', 'JsonResponse', 'RedirectResponse', 'HtmlResponse']` | Class short names (or FQN suffixes) whose constructor args are inspected |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidMagicHttpStatusCodeRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [ForbidMagicHttpStatusCodeRector] Replace %d with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md',
]);
```

## Examples

### Before (helper function)
```php
return response('Not Found', 404);
return response('Created', 201);
```

### After (helper function)
```php
// TODO: Replace 404 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return response('Not Found', 404);
// TODO: Replace 201 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return response('Created', 201);
```

### Before (new Response)
```php
return new Response('OK', 200);
return new JsonResponse(['error' => 'Unauthorized'], 401);
```

### After (new Response)
```php
// TODO: Replace 200 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return new Response('OK', 200);
// TODO: Replace 401 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return new JsonResponse(['error' => 'Unauthorized'], 401);
```

### Before (withStatus)
```php
return $response->withStatus(503);
```

### After (withStatus)
```php
// TODO: Replace 503 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return $response->withStatus(503);
```

### Skipped (named constant)
```php
return response('Not Found', HttpStatus::NOT_FOUND);             // no TODO
return new Response('OK', Status::OK);                            // no TODO
return $response->withStatus(HttpCode::SERVICE_UNAVAILABLE);      // no TODO
```

### Skipped (non-HTTP integer)
```php
return response('Hello', 42);   // 42 is not a known HTTP status code
return new Response('chunk', 7); // 7 is not a known HTTP status code
```

## Resolution

When you see the TODO comment from this rule:

1. Define an enum or constants class for HTTP status codes used in the project (e.g., `HttpStatus::NOT_FOUND = 404`) or use an existing library constant.
2. Replace the integer literal with the named reference.
3. The TODO comment will disappear on the next Rector run.

## Related Rules

- [`ForbidMagicStringArrayKeyRector`](ForbidMagicStringArrayKeyRector.md) — flags raw string keys, requiring named class constants
