# Routing

Attribute-oriented, enum-centric routing. Routes are string-backed enum cases decorated with `#[Route]` attributes. The framework discovers, guards, and registers them automatically.

## Concepts

| Concept | Mechanism | Purpose |
|---------|-----------|---------|
| Route | `#[Route]` on enum case | Declares an HTTP operation on a path |
| Guard | `#[Guarded]` on enum class or case | Conditionally excludes routes from registration |
| Middleware | `#[Middleware]` on enum class or case | Attaches PSR-15 middleware to routes |
| Action strategy | `#[ActionStrategy]` on class | Marks a class as a handler strategy with `toCallable()` |
| Param | `{placeholder}` in enum value | URL parameters derived from the path |
| Method binding | `#[HandlesMethod]` on controller method | Declares which HTTP method a controller method handles |
| Test coverage | `#[CoversRoute]` on test class | Declares which routes a test covers |

## Defining routes

Routes are string-backed enum cases. The enum value is the URL path. Each case carries one or more `#[Route]` attributes declaring HTTP operations.

```php
enum RouteList: string
{
    // Class-string — resolves via ControllerDispatch: invokes if callable,
    // or dispatches via #[HandlesMethod]
    #[Route('GET', ProfileController::class, 'User profile')]
    case profile = '/users/{id}';

    // Array callable — instantiates and calls the named method
    #[Route('GET', [ReportController::class, 'download'], 'Download report')]
    case report = '/reports/{id}/download';

    // Closure — direct invocation
    #[Route('GET', static function (): JsonResponse {
        return new JsonResponse(['ok' => true]);
    }, 'Health check')]
    case ping = '/_ping';

}
```

Multiple `#[Route]` attributes on the same case register multiple HTTP methods on the same path.

### Action strategies

Action strategy classes carry the `#[ActionStrategy]` attribute and implement a `toCallable(BackedEnum, HttpMethod): callable` method via duck typing (no interface). The registrar dispatches action objects via `toCallable()` when the method exists, then falls through to `Closure`, class-string, and array-callable matching.

| Action | Behavior |
|--------|----------|
| `new StaticView(View::name)` | Renders a Blade template with no controller |
| `new Form(view, controller, request, view_model)` | Renders a form with an empty ViewModel |
| `new Validated(controller, request, view_model)` | Validates request body; re-renders sibling Form on error, delegates to controller on success |

```php
enum RouteList: string
{
    #[Route('GET', new StaticView(View::home), 'Marketing home page')]
    case home = '/';

    #[Route(
        'GET',
        new Form(
            View::register,
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        'New user registration form',
    )]
    #[Route(
        'POST',
        new Validated(
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        null,
    )]
    case register = '/register';
}
```

### Parameterized routes

Parameters are declared as `{placeholders}` in the route path. `RouteUrl::paramsOf()` extracts parameter names by parsing the path — no separate attribute is needed.

```php
#[Route('GET', MemberController::class, 'Org member')]
case member = '/orgs/{org_id}/members/{user_id}';
```

`RouteUrl` validates that all path params are provided and no extras are passed.

## Route discovery

Route provider enums are passed explicitly as an array to `App::boot()` at the worker entrypoint:

```php
$App = App::boot($base_dir, routeProviders: [
    RouteList::class,
    FrameworkDevRouteList::class,
    DevRouteList::class,
]);
```

`RouteRegistrar::register()` accepts this list and iterates every case across all providers.

To add a new route file:

1. Create a string-backed enum with `#[Route]` cases.
2. Add the enum class to the `routeProviders` array in `public/index.php`.
3. Run `./run fix:all`.

## Guards

Guards conditionally exclude routes from registration based on runtime config.

```php
#[Guarded(RouteGuard::devOnly)]
enum DevRouteList: string
{
    #[Route('GET', new StaticView(View::styleguide), 'Styleguide')]
    case styleguide = '/_styleguide';
}
```

| Guard | Condition |
|-------|-----------|
| `RouteGuard::devOnly` | Registered only when `APP_ENV` is not production |

### Resolution order

1. Case-level `#[Guarded]` takes precedence.
2. Class-level `#[Guarded]` applies to all cases that lack a case-level override.
3. No attribute means the route is always registered.

## Middleware

PSR-15 middleware is declared with `#[Middleware]`. Class-level middleware runs first, case-level middleware is additive.

```php
#[Middleware(AuthMiddleware::class)]
enum ProtectedRoutes: string
{
    #[Route('GET', DashboardController::class, 'Dashboard')]
    case dashboard = '/dashboard';

    #[Middleware(AdminMiddleware::class)]
    #[Route('GET', AdminController::class, 'Admin panel')]
    case admin = '/admin';
}
```

The `admin` case gets `[AuthMiddleware, AdminMiddleware]`. The `dashboard` case gets `[AuthMiddleware]`.

## Controllers

Controllers declare their method dispatch via attributes.

```php
readonly class RegisterController
{
    #[HandlesMethod(HttpMethod::POST)]
    public function post(RegisterRequest $RegisterRequest): ResponseInterface
    {
        CreateUserQuery::create($RegisterRequest);
        return new RedirectResponse(RouteList::login->value);
    }
}
```

`#[HandlesMethod]` maps a method to an HTTP verb. `ControllerDispatch::resolve()` reflects on this to find the correct method. For invokable controllers (single `__invoke` method), `#[HandlesMethod]` is not needed.

## Registration flow

At boot, `RouteRegistrar::register()` processes all discovered providers:

```
For each route provider enum:
  For each case:
    1. Check Guarded::of(case) → skip if guard fails
    2. Resolve Middleware::of(case) → [class-level..., case-level...]
    3. Assert Form/Validated pairing (see below)
    4. For each #[Route] on case:
       a. Resolve handler via action strategy (toCallable or match dispatch)
       b. Register with League\Route\Router (method + path → handler)
       c. Apply middleware stack via lazyMiddleware
```

### Form/Validated pairing (boot-time assertion)

A `Validated` action re-renders a form on validation failure, so it requires a sibling `Form` action on the same enum case. This pairing is enforced at boot time — if a case has a `Validated` action without a `Form` action, `RouteRegistrar` throws `LogicException` before any routes are mapped.

```php
// Valid — Form + Validated on the same case
#[Route('GET', new Form(View::register, controller: RegisterController::class, request: RegisterRequest::class, view_model: RegisterViewModel::class), 'Registration form')]
#[Route('POST', new Validated(controller: RegisterController::class, request: RegisterRequest::class, view_model: RegisterViewModel::class), null)]
case register = '/register';

// Invalid — Validated without Form → LogicException at boot
#[Route('POST', new Validated(controller: RegisterController::class, request: RegisterRequest::class, view_model: RegisterViewModel::class), null)]
case register = '/register';
```

The Form action is resolved once at boot by `Validated::toCallable()` and captured in the handler closure, so no reflection occurs during request dispatch.

### Validated action flow (POST)

When a `Validated` action handles a request:

1. Parse request body into the request class via `::from()`.
2. Validate via `Validator::validate()`.
3. If valid: delegate to the controller handler.
4. If invalid: call `Form::render()` with the request data and validation errors.

## URL generation

### In PHP

```php
use ZeroToProd\Framework\Routes\RouteUrl;

// Simple route
$url = RouteUrl::for(RouteList::home)->render();
// → "/"

// With parameters
$url = RouteUrl::for(RouteList::profile, params: ['id' => '42'])->render();
// → "/users/42"

// With query string
$url = RouteUrl::for(RouteList::search, query: ['q' => 'hello'])->render();
// → "/search?q=hello"

// Stringable — cast automatically
$url = (string) RouteUrl::for(RouteList::home);
```

`RouteUrl::paramsOf()` extracts expected params from `{placeholders}` in the path. Missing or extra params throw `InvalidArgumentException`.

### In Blade templates

The `@route` directive compiles to `RouteUrl::for()`:

```blade
<a href="@route(RouteList::home)">Home</a>

<form action="@route(RouteList::register)">

{{-- With params --}}
<a href="@route(RouteList::profile, ['id' => $user->id])">Profile</a>

{{-- With params and query --}}
<a href="@route(RouteList::search, [], ['q' => $term])">Search</a>
```

## Testing

Test classes declare route coverage with `#[CoversRoute]`:

```php
#[CoversRoute(RouteList::register)]
class RegisterRouteTest extends IntegrationTestCase
{
    public function test_get_renders_form(): void
    {
        $response = $this->get(RouteList::register->value);
        self::assertSame(200, $response->getStatusCode());
    }
}
```

## Introspection

```bash
# Route manifest (JSON)
./run list:routes

# Attribute graph filtered to routes
./run list:attributes -- --attr=Route
./run list:attributes -- --layer=routes

# Specific route node and neighbors
./run list:attributes -- --node=RouteList
```

## File map

### Framework — Routes

| File | Purpose |
|------|---------|
| `framework/Routes/HttpMethod.php` | HTTP method enum (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`) |
| `framework/Routes/RouteGuard.php` | Registration guard enum (`devOnly`) |
| `framework/Routes/RouteRegistrar.php` | Boot-time registration orchestrator |
| `framework/Routes/RouteUrl.php` | URL builder with path-based param validation |
| `framework/Routes/RouteManifest.php` | JSON key constants for the route manifest endpoint |
| `framework/Routes/ControllerDispatch.php` | Resolves controller class-strings to callables |
| `framework/Routes/FrameworkDevRouteList.php` | Framework dev routes (opcache, route manifest) |

### Framework — Action strategies

| File | Purpose |
|------|---------|
| `framework/Routes/Actions/ActionStrategy.php` | Marker attribute for action strategy classes |
| `framework/Routes/Actions/StaticView.php` | Renders a Blade view with no controller |
| `framework/Routes/Actions/Form.php` | Renders a form with an empty ViewModel; provides `render()` for error re-rendering |
| `framework/Routes/Actions/Validated.php` | Validates request body, re-renders sibling Form on error, delegates on success |

### Framework — Attributes

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[Route]` | Enum case (repeatable) | Declares HTTP operation (`HttpMethod` or string) with action and description (`string` or `BackedEnum`) |
| `#[Guarded]` | Enum class or case | Applies registration guard |
| `#[Middleware]` | Enum class or case | Attaches PSR-15 middleware |
| `#[ActionStrategy]` | Class | Marks a class as an action strategy with `toCallable()` |
| `#[HandlesMethod]` | Controller method | Binds method to HTTP verb for `ControllerDispatch` |
| `#[CoversRoute]` | Test class | Declares route test coverage |

### Framework — Blade

| File | Purpose |
|------|---------|
| `framework/Blade/Registrars/RouteUrlRegistrar.php` | Registers the `@route` Blade directive |
