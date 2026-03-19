# ADR-007: Constants Name Things, Enums Limit Choices, Attributes Define Properties

## Status
Accepted

## Context
PHP offers several mechanisms for attaching meaning to values: class constants, backed enums, and attributes. Without a clear rule for when to reach for each, developers (and AI agents) default to bare strings — leading to duplication, typos, and values that are impossible to trace statically.

The project already encodes this philosophy in a Rector rule message (`SuggestDuplicateStringConstantRector`): *"Consts name things, enums limit choices, attributes define properties."* This ADR formalizes that guidance.

## Decision

### Constants name things
Use `public const string` when a value is an **identifier** — a key, label, header name, or path that the code refers to by name. The constant gives the string a home so it can be found, renamed, and validated in one place.

| Example | File | What it names |
|---------|------|---------------|
| `LogContext::event` | `src/LogContext.php` | Key in a log context array |
| `View::home->value` | `src/Blade/View.php` | Blade template identifier |
| `Config::blade_cache_dir` | `src/Config.php` | Property key for `DataModel::from()` |
| `ErrorViewModel::view_key` | `src/ViewModels/ErrorViewModel.php` | Array key when passing data to Blade |
| `Header::request_id` | `src/Header.php` | HTTP header name `X-Request-ID` |
| `Env::MAX_REQUESTS` | `src/Env.php` | `$_SERVER` key |

**Rule of thumb:** if a string appears as an array key, method argument, or identifier more than once — or could be misspelled — it should be a constant.

### Enums limit choices
Use a backed enum when a value must be **one of a fixed set**. The enum makes invalid values unrepresentable at the type level.

| Example | File | What it constrains |
|---------|------|--------------------|
| `AppEnv::production` | `src/AppEnv.php` | App can only be `production` or `development` |
| `Route::home` | `src/Routes/Route.php` | Only declared routes can be registered or linked |
| `HTTP_METHOD::GET` | `src/Routes/HTTP_METHOD.php` | Only valid HTTP verbs accepted |
| `LogLevel::Error` | `src/LogLevel.php` | Log severity limited to Debug/Info/Warn/Error |

**Rule of thumb:** if adding a new value requires the system to handle it (a new route, a new environment, a new HTTP method) — it's an enum. The type system and `match` exhaustiveness then force every consumer to account for the new case.

### Attributes define properties
Use a PHP attribute when a class or property needs **metadata that alters behavior** — either at runtime (e.g., DataModel population) or at build time (e.g., Rector code generation).

| Example | File | What it defines |
|---------|------|--------------------|
| `#[ViewModel]` | `src/Helpers/ViewModel.php` | Marks a class for Rector to auto-generate a `view_key` constant |
| `#[Describe([Describe::default => AppEnv::production])]` | `src/Config.php` | Tells DataModel to use a default value when key is missing |
| `#[Describe([Describe::cast => ...])]` | (convention) | Tells DataModel to transform the raw value before assignment |

**Rule of thumb:** if the decision is about *how* a property behaves (default value, casting, required/optional, ignore) — use an attribute. If it's about *what* a class is (a view model, a data transfer object) — use a marker attribute and let Rector enforce the consequences.

## How They Compose
The three mechanisms layer together. A `Config` class demonstrates all three:

```php
#[Describe([Describe::default => AppEnv::production])]  // attribute: defines default + cast behavior
public AppEnv $AppEnv;                                   // enum: limits value to production|development
public const string AppEnv = 'AppEnv';                   // constant: names the array key for ::from()
```

Population uses all three: `Config::from([Config::AppEnv => AppEnv::production->value])`.

## Consequences
- **Rector enforces the boundaries.** `SuggestDuplicateStringConstantRector` flags repeated strings that should be constants. `SuggestEnumForStringPropertyRector` flags string properties that should be enums. `AddViewKeyConstantRector` generates constants from attribute markers.
- **AI agents can distinguish intent.** Seeing a constant means "this is a name." Seeing an enum means "these are the only valid values." Seeing an attribute means "this changes how the property/class behaves."
- **New contributors have a clear decision tree.** Instead of asking "should this be a constant or an enum?", the answer follows from the question: are you naming something, constraining choices, or defining behavior?
