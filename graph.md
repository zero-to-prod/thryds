# Attribute Graph

## Index

| Layer | Nodes |
|-------|-------|
| attributes | `KeySource`, `SourceOfTruthConcept` |
| controllers | `RegisterController` |
| core | `App`, `AppEnv`, `Config`, `DevPath`, `DevPathGroup`, `Env`, `ExceptionHandler`, `Header`, `LogContext`, `LogLevel`, `MigrationStatus`, `Migrator`, `OpcacheStatus`, `RequestId` |
| requests | `RegisterRequest` |
| routing | `HttpMethod`, `Route`, `RouteManifest` |
| schema | `Charset`, `Collation`, `DataType`, `Engine`, `ReferentialAction` |
| tables | `Migration`, `TableName`, `User`, `UserColumns` |
| ui | `AlertVariant`, `ButtonSize`, `ButtonVariant`, `Domain`, `InputType`, `Layout`, `Props` |
| validation | `Rule` |
| viewmodels | `ErrorMessages`, `ErrorViewModel`, `RegisterViewModel` |
| views | `BladeDirective`, `Component`, `ScalarDefault`, `View`, `Vite` |

## Mutation Instructions

### `AlertVariant` (addCase via ClosedSet)

    1. Add enum case.
    2. Add conditional class in templates/components/alert.blade.php.

### `AppEnv` (addCase via ClosedSet)

1. Add enum case.
2. Handle in Config::__construct() and App::boot().

### `BladeDirective` (addCase via ClosedSet)

1. Add enum case.
2. Register directive in BladeDirectives::register().

### `ButtonSize` (addCase via ClosedSet)

    1. Add enum case.
    2. Add conditional class in templates/components/button.blade.php.

### `ButtonVariant` (addCase via ClosedSet)

1. Add enum case.
2. Add conditional class in templates/components/button.blade.php.

### `Charset` (addCase via ClosedSet)

1. Add enum case.
2. Verify MySQL/MariaDB support for the charset.

### `Collation` (addCase via ClosedSet)

1. Add enum case.
2. Verify MySQL/MariaDB support for the collation.

### `Component` (addCase via ClosedSet)

    1. Add entry to thryds.yaml components section.
    2. Run ./run sync:manifest.
    3. Implement component template and add example to styleguide.
    4. Run ./run fix:all.

### `DataType` (addCase via ClosedSet)

1. Add enum case.
2. Handle DDL generation in any schema-to-SQL tooling.

### `DevPath` (addCase via ClosedSet)

1. Add enum case with #[Group(DevPathGroup::…)] attribute. Both consumers use DevFilter::isDevPath() so no further changes needed.

### `DevPathGroup` (addCase via ClosedSet)

Add enum case. Then use it in a #[Group] attribute on DevPath cases.

### `Domain` (addCase via ClosedSet)

Add enum case. Then use it in a #[ClosedSet] attribute on a new backed enum.

### `Engine` (addCase via ClosedSet)

1. Add enum case.
2. Verify MySQL/MariaDB support for the engine.

### `Env` (addCase via SourceOfTruth)

    1. Add constant.
    2. Add to compose.yaml environment section if needed.
    3. Add to .env.example.

### `ErrorMessages` (addCase via ClosedSet)

Add enum case. Use the case value wherever ErrorViewModel::$message is set.

### `Header` (addKey via KeyRegistry)

1. Add constant. 2. Reference via Header::NAME where needed.

### `HttpMethod` (addCase via ClosedSet)

Add enum case. No other changes needed — RouteRegistrar::register() accepts any HttpMethod.

### `InputType` (addCase via ClosedSet)

1. Add enum case.
2. Verify the value is a valid HTML input type.

### `KeySource` (addCase via ClosedSet)

Add enum case. Then use it in a #[KeyRegistry] attribute on a new constants class.

### `Layout` (addCase via ClosedSet)

1. Add enum case.
2. Create templates/{value}.blade.php layout template.

### `LogContext` (addKey via KeyRegistry)

1. Add constant. 2. Pass in context array via Log::method([LogContext::KEY => $value]).

### `LogLevel` (addCase via ClosedSet)

Add enum case. No other changes needed — Log methods accept any LogLevel.

### `Migration` (addCase via ClosedSet)

1. Add enum case with #[Column] attribute.
2. Write a migration to ALTER TABLE migrations ADD COLUMN ...

### `MigrationStatus` (addCase via ClosedSet)

Add enum case. Then handle in Migrator::status().

### `Migrator` (addKey via KeyRegistry)

1. Add constant. 2. Reference via Migrator::CONST_NAME where needed.

### `Props` (addCase via ClosedSet)

1. Add enum case.
2. Use it in a #[Prop] attribute on the relevant Component case.

### `ReferentialAction` (addCase via ClosedSet)

1. Add enum case.
2. Verify the action is supported by the target database engine.

### `Route` (addCase via ClosedSet)

1. Add entry to thryds.yaml routes section.
2. Run ./run sync:manifest.
3. Implement controller logic (if controller route).
4. Run ./run fix:all.

### `RouteManifest` (addKey via KeyRegistry)

1. Add constant. 2. Add the corresponding field in RouteRegistrar::register() manifest map.

### `Rule` (addCase via ClosedSet)

Add enum case. Implement passes() and message() match arms.

### `ScalarDefault` (addCase via ClosedSet)

Add enum case matching PHP type name, then add its zero-value to zeroValue().

### `SourceOfTruthConcept` (addCase via ClosedSet)

1. Add enum case. No other changes needed — SourceOfTruth consumers discover it by type.

### `User` (addCase via ClosedSet)

1. Add enum case with #[Column] attribute.
2. Write a migration to ALTER TABLE users ADD COLUMN ...

### `View` (addCase via ClosedSet)

    1. Add entry to thryds.yaml views section.
    2. Run ./run sync:manifest.
    3. Implement template content and stubData() if ViewModel is used.
    4. Run ./run fix:all.

### `Vite` (addKey via KeyRegistry)

1. Add constant. 2. Register a Blade directive in BladeDirectives::register(). 3. Add entry to vite.config.js input array.

## Nodes

### Attributes

#### `KeySource` (enum)

- **File:** `src/Attributes/KeySource.php`
- **ClosedSet:** Domain: key_sources, addCase: Add enum case. Then use it in a #[KeyRegistry] attribute on a new constants class.

**Cases:**

| Case | Value |
|------|-------|
| `http_headers` | `HTTP headers` |
| `log_context_array` | `Log context array` |
| `opcache_get_status` | `opcache_get_status()` |
| `server_env` | `$_SERVER / $_ENV` |
| `migrations_table` | `migrations tracking table` |
| `vite_entry_points` | `Vite entry points` |
| `route_manifest` | `Route manifest JSON` |

#### `SourceOfTruthConcept` (enum)

- **File:** `src/Attributes/SourceOfTruthConcept.php`
- **ClosedSet:** Domain: source_of_truth_concepts, addCase: 1. Add enum case. No other changes needed — SourceOfTruth consumers discover it by type.

**Cases:**

| Case | Value |
|------|-------|
| `blade_template_names` | `Blade template names` |
| `environment_variable_keys` | `environment variable keys` |
| `route_paths` | `route paths` |

### Controllers

#### `RegisterController` (class)

- **File:** `src/Controllers/RegisterController.php`
- **Persists:** model: ZeroToProd\Thryds\Tables\User
- **RedirectsTo:** Route: /login

### Core

#### `App` (class)

- **File:** `src/App.php`

**Properties:**

| Property | Attributes |
|----------|------------|
| `Blade` | Bind |
| `Database` | Bind |

**Methods:**

| Method | Attributes |
|--------|------------|
| `boot()` | Requirement: ids: PERF-001 |

#### `AppEnv` (enum)

- **File:** `src/AppEnv.php`
- **ClosedSet:** Domain: application_environment, addCase: 1. Add enum case.
2. Handle in Config::__construct() and App::boot().

**Cases:**

| Case | Value |
|------|-------|
| `production` | `production` |
| `development` | `development` |

#### `Config` (class)

- **File:** `src/Config.php`

**Properties:**

| Property | Attributes |
|----------|------------|
| `AppEnv` | Describe: attributes: default: production |
| `blade_cache_dir` | Describe: attributes: default: /app/var/cache/blade |
| `template_dir` | Describe: attributes: default: /app/templates |

#### `DevPath` (enum)

- **File:** `src/DevPath.php`
- **ClosedSet:** Domain: dev_paths, addCase: 1. Add enum case with #[Group(DevPathGroup::…)] attribute. Both consumers use DevFilter::isDevPath() so no further changes needed.

**Cases:**

| Case | Value | Attributes |
|------|-------|------------|
| `phpunit` | `/vendor/phpunit/` | Group: BackedEnum: vendor |
| `phpstan` | `/vendor/phpstan/` | Group: BackedEnum: vendor |
| `rector` | `/vendor/rector/` | Group: BackedEnum: vendor |
| `friendsofphp` | `/vendor/friendsofphp/` | Group: BackedEnum: vendor |
| `myclabs` | `/vendor/myclabs/` | Group: BackedEnum: vendor |
| `sebastian` | `/vendor/sebastian/` | Group: BackedEnum: vendor |
| `theseer` | `/vendor/theseer/` | Group: BackedEnum: vendor |
| `nikic_php_parser` | `/vendor/nikic/php-parser/` | Group: BackedEnum: vendor |
| `var_cache` | `/var/cache/` | Group: BackedEnum: excluded_dir |
| `tests` | `/tests/` | Group: BackedEnum: excluded_dir |
| `utils` | `/utils/` | Group: BackedEnum: excluded_dir |

#### `DevPathGroup` (enum)

- **File:** `src/DevPathGroup.php`
- **ClosedSet:** Domain: dev_path_groups, addCase: Add enum case. Then use it in a #[Group] attribute on DevPath cases.

**Cases:**

| Case | Value |
|------|-------|
| `vendor` | `vendor` |
| `excluded_dir` | `excluded_dir` |

#### `Env` (class)

- **File:** `src/Env.php`
- **SourceOfTruth:** SourceOfTruthConcept: environment variable keys, addCase:     1. Add constant.
    2. Add to compose.yaml environment section if needed.
    3. Add to .env.example.
- **KeyRegistry:** KeySource: $_SERVER / $_ENV, superglobals: _SERVER, _ENV, addKey: 

#### `ExceptionHandler` (class)

- **File:** `src/ExceptionHandler.php`

**Methods:**

| Method | Attributes |
|--------|------------|
| `handleHttpException()` | HandlesException: exception: League\Route\Http\Exception |
| `handleThrowable()` | HandlesException: exception: Throwable |

#### `Header` (class)

- **File:** `src/Header.php`
- **KeyRegistry:** KeySource: HTTP headers, superglobals: , addKey: 1. Add constant. 2. Reference via Header::NAME where needed.

#### `LogContext` (class)

- **File:** `src/LogContext.php`
- **KeyRegistry:** KeySource: Log context array, superglobals: , addKey: 1. Add constant. 2. Pass in context array via Log::method([LogContext::KEY => $value]).

#### `LogLevel` (enum)

- **File:** `src/LogLevel.php`
- **ClosedSet:** Domain: log_severity_levels, addCase: Add enum case. No other changes needed — Log methods accept any LogLevel.

**Cases:**

| Case | Value |
|------|-------|
| `Debug` |  |
| `Info` |  |
| `Warn` |  |
| `Error` |  |

#### `MigrationStatus` (enum)

- **File:** `src/MigrationStatus.php`
- **ClosedSet:** Domain: migration_statuses, addCase: Add enum case. Then handle in Migrator::status().

**Cases:**

| Case | Value |
|------|-------|
| `pending` | `pending` |
| `applied` | `applied` |
| `modified` | `modified` |

#### `Migrator` (class)

- **File:** `src/Migrator.php`
- **KeyRegistry:** KeySource: migrations tracking table, superglobals: , addKey: 1. Add constant. 2. Reference via Migrator::CONST_NAME where needed.

#### `OpcacheStatus` (class)

- **File:** `src/OpcacheStatus.php`
- **KeyRegistry:** KeySource: opcache_get_status(), superglobals: , addKey: 

#### `RequestId` (class)

- **File:** `src/RequestId.php`
- **Requirement:** ids: TRACE-001, SEC-001

**Methods:**

| Method | Attributes |
|--------|------------|
| `init()` | Requirement: ids: TRACE-001 |
| `reset()` | Requirement: ids: SEC-001 |

### Requests

#### `RegisterRequest` (class)

- **File:** `src/Requests/RegisterRequest.php`

**Properties:**

| Property | Attributes |
|----------|------------|
| `name` | Input: InputType: text, label: Name; Validate: rules: required |
| `handle` | Input: InputType: text, label: Handle; Validate: rules: required |
| `email` | Input: InputType: email, label: Email; Validate: rules: required, email |
| `password` | Input: InputType: password, label: Password; Validate: rules: required, min, 8 |
| `password_confirmation` | Input: InputType: password, label: Confirm Password; Validate: rules: required, matches, password |
| `id` | Column: DataType: CHAR, length: 26, comment: Primary key; PrimaryKey: columns: ; Describe: attributes: nullable: true |
| `email_verified_at` | Column: DataType: TIMESTAMP, nullable: true, comment: Timestamp of email verification; Describe: attributes: nullable: true |
| `created_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record creation time; Describe: attributes: nullable: true |
| `updated_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record last update time; Describe: attributes: nullable: true |

### Routing

#### `HttpMethod` (enum)

- **File:** `src/Routes/HttpMethod.php`
- **ClosedSet:** Domain: http_methods, addCase: Add enum case. No other changes needed — RouteRegistrar::register() accepts any HttpMethod.

**Cases:**

| Case | Value |
|------|-------|
| `GET` | `GET` |
| `POST` | `POST` |
| `PUT` | `PUT` |
| `PATCH` | `PATCH` |
| `DELETE` | `DELETE` |

#### `Route` (enum)

- **File:** `src/Routes/Route.php`
- **ClosedSet:** Domain: url_routes, addCase: 1. Add entry to thryds.yaml routes section.
2. Run ./run sync:manifest.
3. Implement controller logic (if controller route).
4. Run ./run fix:all.

**Cases:**

| Case | Value | Attributes |
|------|-------|------------|
| `home` | `/` | RouteInfo: description: Home; RouteOperation: HttpMethod: GET, description: Marketing home page |
| `about` | `/about` | RouteInfo: description: About; RouteOperation: HttpMethod: GET, description: Company and product information |
| `login` | `/login` | RouteInfo: description: Login; RouteOperation: HttpMethod: GET, description: User authentication form |
| `register` | `/register` | RouteInfo: description: Register; RouteOperation: HttpMethod: GET, description: New user registration form, HttpMethod: POST, description: Handle registration submission |
| `opcache_status` | `/_opcache/status` | DevOnly; RouteInfo: description: OPcache status; RouteOperation: HttpMethod: GET, description: OPcache runtime statistics |
| `opcache_scripts` | `/_opcache/scripts` | DevOnly; RouteInfo: description: OPcache scripts; RouteOperation: HttpMethod: GET, description: Scripts loaded in OPcache |
| `styleguide` | `/_styleguide` | DevOnly; RouteInfo: description: Style guide; RouteOperation: HttpMethod: GET, description: UI component and design token reference |
| `routes` | `/_routes` | DevOnly; RouteInfo: description: Routes; RouteOperation: HttpMethod: GET, description: Machine-readable manifest of all registered routes |

#### `RouteManifest` (class)

- **File:** `src/Routes/RouteManifest.php`
- **KeyRegistry:** KeySource: Route manifest JSON, superglobals: , addKey: 1. Add constant. 2. Add the corresponding field in RouteRegistrar::register() manifest map.

### Schema

#### `Charset` (enum)

- **File:** `src/Schema/Charset.php`
- **ClosedSet:** Domain: sql_charsets, addCase: 1. Add enum case.
2. Verify MySQL/MariaDB support for the charset.

**Cases:**

| Case | Value |
|------|-------|
| `utf8mb4` | `utf8mb4` |

#### `Collation` (enum)

- **File:** `src/Schema/Collation.php`
- **ClosedSet:** Domain: sql_collations, addCase: 1. Add enum case.
2. Verify MySQL/MariaDB support for the collation.

**Cases:**

| Case | Value |
|------|-------|
| `utf8mb4_unicode_ci` | `utf8mb4_unicode_ci` |

#### `DataType` (enum)

- **File:** `src/Schema/DataType.php`
- **ClosedSet:** Domain: sql_data_types, addCase: 1. Add enum case.
2. Handle DDL generation in any schema-to-SQL tooling.

**Cases:**

| Case | Value | Attributes |
|------|-------|------------|
| `BIGINT` | `BIGINT` | SupportsUnsigned; SupportsAutoIncrement |
| `INT` | `INT` | SupportsUnsigned; SupportsAutoIncrement |
| `SMALLINT` | `SMALLINT` | SupportsUnsigned; SupportsAutoIncrement |
| `TINYINT` | `TINYINT` | SupportsUnsigned; SupportsAutoIncrement |
| `VARCHAR` | `VARCHAR` | RequiresLength |
| `CHAR` | `CHAR` | RequiresLength |
| `TEXT` | `TEXT` |  |
| `MEDIUMTEXT` | `MEDIUMTEXT` |  |
| `LONGTEXT` | `LONGTEXT` |  |
| `DATETIME` | `DATETIME` |  |
| `DATE` | `DATE` |  |
| `TIME` | `TIME` |  |
| `TIMESTAMP` | `TIMESTAMP` |  |
| `YEAR` | `YEAR` |  |
| `DECIMAL` | `DECIMAL` | SupportsUnsigned; RequiresPrecisionScale |
| `FLOAT` | `FLOAT` | SupportsUnsigned |
| `DOUBLE` | `DOUBLE` | SupportsUnsigned |
| `BOOLEAN` | `BOOLEAN` |  |
| `JSON` | `JSON` |  |
| `ENUM` | `ENUM` | RequiresValues |
| `SET` | `SET` | RequiresValues |
| `BINARY` | `BINARY` | RequiresLength |
| `VARBINARY` | `VARBINARY` | RequiresLength |
| `BLOB` | `BLOB` |  |
| `MEDIUMBLOB` | `MEDIUMBLOB` |  |
| `LONGBLOB` | `LONGBLOB` |  |

#### `Engine` (enum)

- **File:** `src/Schema/Engine.php`
- **ClosedSet:** Domain: sql_storage_engines, addCase: 1. Add enum case.
2. Verify MySQL/MariaDB support for the engine.

**Cases:**

| Case | Value |
|------|-------|
| `InnoDB` | `InnoDB` |
| `MyISAM` | `MyISAM` |
| `MEMORY` | `MEMORY` |
| `ARCHIVE` | `ARCHIVE` |
| `CSV` | `CSV` |

#### `ReferentialAction` (enum)

- **File:** `src/Schema/ReferentialAction.php`
- **ClosedSet:** Domain: sql_referential_actions, addCase: 1. Add enum case.
2. Verify the action is supported by the target database engine.

**Cases:**

| Case | Value |
|------|-------|
| `CASCADE` | `CASCADE` |
| `SetNull` | `SET NULL` |
| `RESTRICT` | `RESTRICT` |
| `NoAction` | `NO ACTION` |
| `SetDefault` | `SET DEFAULT` |

### Tables

#### `Migration` (class)

- **File:** `src/Tables/Migration.php`
- **ClosedSet:** Domain: database_table_columns, addCase: 1. Add enum case with #[Column] attribute.
2. Write a migration to ALTER TABLE migrations ADD COLUMN ...
- **Table:** TableName: migrations, Engine: InnoDB, Charset: utf8mb4, Collation: utf8mb4_unicode_ci

**Properties:**

| Property | Attributes |
|----------|------------|
| `id` | Column: DataType: VARCHAR, length: 20, comment: Migration id, matching the four-digit prefix of the migration filename (e.g. 0001).; PrimaryKey: columns:  |
| `description` | Column: DataType: VARCHAR, length: 255, comment: Human-readable description from the #[Migration] attribute on the migration class. |
| `checksum` | Column: DataType: VARCHAR, length: 64, comment: SHA-256 hash of the migration file contents at the time it was applied. |
| `applied_at` | Column: DataType: DATETIME, default: CURRENT_TIMESTAMP, comment: Timestamp when the migration was applied. |

#### `TableName` (enum)

- **File:** `src/Tables/TableName.php`

**Cases:**

| Case | Value |
|------|-------|
| `migrations` | `migrations` |
| `users` | `users` |

#### `User` (class)

- **File:** `src/Tables/User.php`
- **ClosedSet:** Domain: database_table_columns, addCase: 1. Add enum case with #[Column] attribute.
2. Write a migration to ALTER TABLE users ADD COLUMN ...
- **Table:** TableName: users, Engine: InnoDB, Charset: utf8mb4, Collation: utf8mb4_unicode_ci

**Properties:**

| Property | Attributes |
|----------|------------|
| `id` | Column: DataType: CHAR, length: 26, comment: Primary key; PrimaryKey: columns: ; Describe: attributes: nullable: true |
| `name` | Column: DataType: VARCHAR, length: 255, comment: Display name; Describe: attributes: nullable: true |
| `handle` | Column: DataType: VARCHAR, length: 30, comment: Unique public username; Describe: attributes: nullable: true |
| `email` | Column: DataType: VARCHAR, length: 255, nullable: true, comment: Contact email address; Describe: attributes: nullable: true |
| `email_verified_at` | Column: DataType: TIMESTAMP, nullable: true, comment: Timestamp of email verification; Describe: attributes: nullable: true |
| `password` | Column: DataType: VARCHAR, length: 255, comment: Hashed password; Describe: attributes: nullable: true |
| `created_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record creation time; Describe: attributes: nullable: true |
| `updated_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record last update time; Describe: attributes: nullable: true |

#### `UserColumns` (trait)

- **File:** `src/Tables/UserColumns.php`

**Properties:**

| Property | Attributes |
|----------|------------|
| `id` | Column: DataType: CHAR, length: 26, comment: Primary key; PrimaryKey: columns: ; Describe: attributes: nullable: true |
| `name` | Column: DataType: VARCHAR, length: 255, comment: Display name; Describe: attributes: nullable: true |
| `handle` | Column: DataType: VARCHAR, length: 30, comment: Unique public username; Describe: attributes: nullable: true |
| `email` | Column: DataType: VARCHAR, length: 255, nullable: true, comment: Contact email address; Describe: attributes: nullable: true |
| `email_verified_at` | Column: DataType: TIMESTAMP, nullable: true, comment: Timestamp of email verification; Describe: attributes: nullable: true |
| `password` | Column: DataType: VARCHAR, length: 255, comment: Hashed password; Describe: attributes: nullable: true |
| `created_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record creation time; Describe: attributes: nullable: true |
| `updated_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record last update time; Describe: attributes: nullable: true |

### Ui

#### `AlertVariant` (enum)

- **File:** `src/UI/AlertVariant.php`
- **ClosedSet:** Domain: alert_variants, addCase:     1. Add enum case.
    2. Add conditional class in templates/components/alert.blade.php.

**Cases:**

| Case | Value |
|------|-------|
| `info` | `info` |
| `danger` | `danger` |
| `success` | `success` |

#### `ButtonSize` (enum)

- **File:** `src/UI/ButtonSize.php`
- **ClosedSet:** Domain: button_sizes, addCase:     1. Add enum case.
    2. Add conditional class in templates/components/button.blade.php.

**Cases:**

| Case | Value |
|------|-------|
| `sm` | `sm` |
| `md` | `md` |
| `lg` | `lg` |

#### `ButtonVariant` (enum)

- **File:** `src/UI/ButtonVariant.php`
- **ClosedSet:** Domain: button_variants, addCase: 1. Add enum case.
2. Add conditional class in templates/components/button.blade.php.

**Cases:**

| Case | Value |
|------|-------|
| `primary` | `primary` |
| `danger` | `danger` |
| `secondary` | `secondary` |

#### `Domain` (enum)

- **File:** `src/UI/Domain.php`
- **ClosedSet:** Domain: closed_set_domains, addCase: Add enum case. Then use it in a #[ClosedSet] attribute on a new backed enum.

**Cases:**

| Case | Value |
|------|-------|
| `closed_set_domains` | `closed_set_domains` |
| `source_of_truth_concepts` | `source_of_truth_concepts` |
| `application_environment` | `application_environment` |
| `blade_directives` | `blade_directives` |
| `blade_components` | `blade_components` |
| `blade_templates` | `blade_templates` |
| `http_methods` | `http_methods` |
| `key_sources` | `key_sources` |
| `log_severity_levels` | `log_severity_levels` |
| `dev_path_groups` | `dev_path_groups` |
| `dev_paths` | `dev_paths` |
| `url_routes` | `url_routes` |
| `error_messages` | `error_messages` |
| `button_variants` | `button_variants` |
| `button_sizes` | `button_sizes` |
| `alert_variants` | `alert_variants` |
| `input_types` | `input_types` |
| `component_props` | `component_props` |
| `layouts` | `layouts` |
| `sql_data_types` | `sql_data_types` |
| `sql_storage_engines` | `sql_storage_engines` |
| `sql_charsets` | `sql_charsets` |
| `sql_collations` | `sql_collations` |
| `sql_referential_actions` | `sql_referential_actions` |
| `database_table_columns` | `database_table_columns` |
| `migration_statuses` | `migration_statuses` |
| `scalar_defaults` | `scalar_defaults` |
| `validation_rules` | `validation_rules` |

#### `InputType` (enum)

- **File:** `src/UI/InputType.php`
- **ClosedSet:** Domain: input_types, addCase: 1. Add enum case.
2. Verify the value is a valid HTML input type.

**Cases:**

| Case | Value |
|------|-------|
| `text` | `text` |
| `email` | `email` |
| `password` | `password` |

#### `Layout` (enum)

- **File:** `src/UI/Layout.php`
- **ClosedSet:** Domain: layouts, addCase: 1. Add enum case.
2. Create templates/{value}.blade.php layout template.

**Cases:**

| Case | Value |
|------|-------|
| `base` | `base` |

#### `Props` (enum)

- **File:** `src/UI/Props.php`
- **ClosedSet:** Domain: component_props, addCase: 1. Add enum case.
2. Use it in a #[Prop] attribute on the relevant Component case.

**Cases:**

| Case | Value |
|------|-------|
| `variant` | `variant` |
| `size` | `size` |
| `type` | `type` |
| `label` | `label` |
| `error` | `error` |

### Validation

#### `Rule` (enum)

- **File:** `src/Validation/Rule.php`
- **ClosedSet:** Domain: validation_rules, addCase: Add enum case. Implement passes() and message() match arms.

**Cases:**

| Case | Value |
|------|-------|
| `required` | `required` |
| `email` | `email` |
| `min` | `min` |
| `max` | `max` |
| `matches` | `matches` |

### Viewmodels

#### `ErrorMessages` (enum)

- **File:** `src/ViewModels/ErrorMessages.php`
- **ClosedSet:** Domain: error_messages, addCase: Add enum case. Use the case value wherever ErrorViewModel::$message is set.

**Cases:**

| Case | Value |
|------|-------|
| `test` | `test` |

#### `ErrorViewModel` (class)

- **File:** `src/ViewModels/ErrorViewModel.php`
- **ViewModel**

#### `RegisterViewModel` (class)

- **File:** `src/ViewModels/RegisterViewModel.php`
- **ViewModel**

**Properties:**

| Property | Attributes |
|----------|------------|
| `name_error` | Describe: attributes: nullable: true |
| `email_error` | Describe: attributes: nullable: true |
| `handle_error` | Describe: attributes: nullable: true |
| `password_error` | Describe: attributes: nullable: true |
| `password_confirmation_error` | Describe: attributes: nullable: true |
| `id` | Column: DataType: CHAR, length: 26, comment: Primary key; PrimaryKey: columns: ; Describe: attributes: nullable: true |
| `name` | Column: DataType: VARCHAR, length: 255, comment: Display name; Describe: attributes: nullable: true |
| `handle` | Column: DataType: VARCHAR, length: 30, comment: Unique public username; Describe: attributes: nullable: true |
| `email` | Column: DataType: VARCHAR, length: 255, nullable: true, comment: Contact email address; Describe: attributes: nullable: true |
| `email_verified_at` | Column: DataType: TIMESTAMP, nullable: true, comment: Timestamp of email verification; Describe: attributes: nullable: true |
| `password` | Column: DataType: VARCHAR, length: 255, comment: Hashed password; Describe: attributes: nullable: true |
| `created_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record creation time; Describe: attributes: nullable: true |
| `updated_at` | Column: DataType: TIMESTAMP, default: CURRENT_TIMESTAMP, comment: Record last update time; Describe: attributes: nullable: true |

### Views

#### `BladeDirective` (enum)

- **File:** `src/Blade/BladeDirective.php`
- **ClosedSet:** Domain: blade_directives, addCase: 1. Add enum case.
2. Register directive in BladeDirectives::register().

**Cases:**

| Case | Value |
|------|-------|
| `production` | `production` |
| `env` | `env` |
| `vite` | `vite` |
| `htmx` | `htmx` |
| `hotReload` | `hotReload` |

#### `Component` (enum)

- **File:** `src/Blade/Component.php`
- **ClosedSet:** Domain: blade_components, addCase:     1. Add entry to thryds.yaml components section.
    2. Run ./run sync:manifest.
    3. Implement component template and add example to styleguide.
    4. Run ./run fix:all.

**Cases:**

| Case | Value | Attributes |
|------|-------|------------|
| `alert` | `alert` | Prop: Props: variant, default: info |
| `button` | `button` | Prop: Props: variant, default: primary, Props: size, default: md, Props: type, default: button |
| `card` | `card` |  |
| `form_group` | `form-group` | Prop: Props: label, default: , Props: error, default:  |
| `input` | `input` | Prop: Props: type, default: text |

#### `ScalarDefault` (enum)

- **File:** `src/Blade/ScalarDefault.php`
- **ClosedSet:** Domain: scalar_defaults, addCase: Add enum case matching PHP type name, then add its zero-value to zeroValue().

**Cases:**

| Case | Value |
|------|-------|
| `string` | `string` |
| `int` | `int` |
| `float` | `float` |
| `bool` | `bool` |
| `array` | `array` |

#### `View` (enum)

- **File:** `src/Blade/View.php`
- **ClosedSet:** Domain: blade_templates, addCase:     1. Add entry to thryds.yaml views section.
    2. Run ./run sync:manifest.
    3. Implement template content and stubData() if ViewModel is used.
    4. Run ./run fix:all.

**Cases:**

| Case | Value | Attributes |
|------|-------|------------|
| `about` | `about` | ExtendsLayout: Layout: base; PageTitle: title: About — Thryds; UsesComponent: components: card |
| `error` | `error` | ExtendsLayout: Layout: base; PageTitle: title: Error — Thryds; UsesComponent: components: alert, card; ReceivesViewModel: viewModels: ZeroToProd\Thryds\ViewModels\ErrorViewModel |
| `home` | `home` | ExtendsLayout: Layout: base; PageTitle: title: Thryds; UsesComponent: components: card, button |
| `login` | `login` | ExtendsLayout: Layout: base; PageTitle: title: Login — Thryds; UsesComponent: components: card, form-group, input, button |
| `register` | `register` | ExtendsLayout: Layout: base; PageTitle: title: Register — Thryds; UsesComponent: components: card, form-group, input, button; ReceivesViewModel: viewModels: ZeroToProd\Thryds\ViewModels\RegisterViewModel |
| `styleguide` | `styleguide` | ExtendsLayout: Layout: base; PageTitle: title: Styleguide — Thryds; UsesComponent: components: alert, button, card, form-group, input |

#### `Vite` (class)

- **File:** `src/Blade/Vite.php`
- **KeyRegistry:** KeySource: Vite entry points, superglobals: , addKey: 1. Add constant. 2. Register a Blade directive in BladeDirectives::register(). 3. Add entry to vite.config.js input array.

## Edges

| From | To | Relationship | Source |
|------|----|-------------|--------|
| `AppEnv` | `Domain` | closedset |  |
| `KeySource` | `Domain` | closedset |  |
| `SourceOfTruthConcept` | `Domain` | closedset |  |
| `BladeDirective` | `Domain` | closedset |  |
| `Component` | `Domain` | closedset |  |
| `Component` | `Props` | prop | case:alert |
| `Component` | `AlertVariant` | prop | case:alert |
| `Component` | `Props` | prop | case:button |
| `Component` | `ButtonVariant` | prop | case:button |
| `Component` | `ButtonSize` | prop | case:button |
| `Component` | `Props` | prop | case:form_group |
| `Component` | `Props` | prop | case:input |
| `Component` | `InputType` | prop | case:input |
| `ScalarDefault` | `Domain` | closedset |  |
| `View` | `Domain` | closedset |  |
| `View` | `Layout` | extendslayout | case:about |
| `View` | `Component` | usescomponent | case:about |
| `View` | `Layout` | extendslayout | case:error |
| `View` | `Component` | usescomponent | case:error |
| `View` | `ErrorViewModel` | receivesviewmodel | case:error |
| `View` | `Layout` | extendslayout | case:home |
| `View` | `Component` | usescomponent | case:home |
| `View` | `Layout` | extendslayout | case:login |
| `View` | `Component` | usescomponent | case:login |
| `View` | `Layout` | extendslayout | case:register |
| `View` | `Component` | usescomponent | case:register |
| `View` | `RegisterViewModel` | receivesviewmodel | case:register |
| `View` | `Layout` | extendslayout | case:styleguide |
| `View` | `Component` | usescomponent | case:styleguide |
| `Vite` | `KeySource` | keyregistry |  |
| `Config` | `AppEnv` | describe | property:AppEnv |
| `RegisterController` | `User` | persists |  |
| `RegisterController` | `Route` | redirectsto |  |
| `DevPath` | `Domain` | closedset |  |
| `DevPath` | `DevPathGroup` | group | case:phpunit |
| `DevPath` | `DevPathGroup` | group | case:phpstan |
| `DevPath` | `DevPathGroup` | group | case:rector |
| `DevPath` | `DevPathGroup` | group | case:friendsofphp |
| `DevPath` | `DevPathGroup` | group | case:myclabs |
| `DevPath` | `DevPathGroup` | group | case:sebastian |
| `DevPath` | `DevPathGroup` | group | case:theseer |
| `DevPath` | `DevPathGroup` | group | case:nikic_php_parser |
| `DevPath` | `DevPathGroup` | group | case:var_cache |
| `DevPath` | `DevPathGroup` | group | case:tests |
| `DevPath` | `DevPathGroup` | group | case:utils |
| `DevPathGroup` | `Domain` | closedset |  |
| `Env` | `SourceOfTruthConcept` | sourceoftruth |  |
| `Env` | `KeySource` | keyregistry |  |
| `ExceptionHandler` | `Exception` | handlesexception | method:handleHttpException |
| `Header` | `KeySource` | keyregistry |  |
| `LogContext` | `KeySource` | keyregistry |  |
| `LogLevel` | `Domain` | closedset |  |
| `MigrationStatus` | `Domain` | closedset |  |
| `Migrator` | `KeySource` | keyregistry |  |
| `OpcacheStatus` | `KeySource` | keyregistry |  |
| `RegisterRequest` | `InputType` | input | property:name |
| `RegisterRequest` | `Rule` | validate | property:name |
| `RegisterRequest` | `InputType` | input | property:handle |
| `RegisterRequest` | `Rule` | validate | property:handle |
| `RegisterRequest` | `InputType` | input | property:email |
| `RegisterRequest` | `Rule` | validate | property:email |
| `RegisterRequest` | `InputType` | input | property:password |
| `RegisterRequest` | `Rule` | validate | property:password |
| `RegisterRequest` | `InputType` | input | property:password_confirmation |
| `RegisterRequest` | `Rule` | validate | property:password_confirmation |
| `RegisterRequest` | `DataType` | column | property:id |
| `RegisterRequest` | `DataType` | column | property:email_verified_at |
| `RegisterRequest` | `DataType` | column | property:created_at |
| `RegisterRequest` | `DataType` | column | property:updated_at |
| `HttpMethod` | `Domain` | closedset |  |
| `Route` | `Domain` | closedset |  |
| `Route` | `HttpMethod` | routeoperation | case:home |
| `Route` | `HttpMethod` | routeoperation | case:about |
| `Route` | `HttpMethod` | routeoperation | case:login |
| `Route` | `HttpMethod` | routeoperation | case:register |
| `Route` | `HttpMethod` | routeoperation | case:opcache_status |
| `Route` | `HttpMethod` | routeoperation | case:opcache_scripts |
| `Route` | `HttpMethod` | routeoperation | case:styleguide |
| `Route` | `HttpMethod` | routeoperation | case:routes |
| `RouteManifest` | `KeySource` | keyregistry |  |
| `Charset` | `Domain` | closedset |  |
| `Collation` | `Domain` | closedset |  |
| `DataType` | `Domain` | closedset |  |
| `Engine` | `Domain` | closedset |  |
| `ReferentialAction` | `Domain` | closedset |  |
| `Migration` | `Domain` | closedset |  |
| `Migration` | `TableName` | table |  |
| `Migration` | `Engine` | table |  |
| `Migration` | `Charset` | table |  |
| `Migration` | `Collation` | table |  |
| `Migration` | `DataType` | column | property:id |
| `Migration` | `DataType` | column | property:description |
| `Migration` | `DataType` | column | property:checksum |
| `Migration` | `DataType` | column | property:applied_at |
| `User` | `Domain` | closedset |  |
| `User` | `TableName` | table |  |
| `User` | `Engine` | table |  |
| `User` | `Charset` | table |  |
| `User` | `Collation` | table |  |
| `User` | `DataType` | column | property:id |
| `User` | `DataType` | column | property:name |
| `User` | `DataType` | column | property:handle |
| `User` | `DataType` | column | property:email |
| `User` | `DataType` | column | property:email_verified_at |
| `User` | `DataType` | column | property:password |
| `User` | `DataType` | column | property:created_at |
| `User` | `DataType` | column | property:updated_at |
| `UserColumns` | `DataType` | column | property:id |
| `UserColumns` | `DataType` | column | property:name |
| `UserColumns` | `DataType` | column | property:handle |
| `UserColumns` | `DataType` | column | property:email |
| `UserColumns` | `DataType` | column | property:email_verified_at |
| `UserColumns` | `DataType` | column | property:password |
| `UserColumns` | `DataType` | column | property:created_at |
| `UserColumns` | `DataType` | column | property:updated_at |
| `AlertVariant` | `Domain` | closedset |  |
| `ButtonSize` | `Domain` | closedset |  |
| `ButtonVariant` | `Domain` | closedset |  |
| `InputType` | `Domain` | closedset |  |
| `Layout` | `Domain` | closedset |  |
| `Props` | `Domain` | closedset |  |
| `Rule` | `Domain` | closedset |  |
| `ErrorMessages` | `Domain` | closedset |  |
| `RegisterViewModel` | `DataType` | column | property:id |
| `RegisterViewModel` | `DataType` | column | property:name |
| `RegisterViewModel` | `DataType` | column | property:handle |
| `RegisterViewModel` | `DataType` | column | property:email |
| `RegisterViewModel` | `DataType` | column | property:email_verified_at |
| `RegisterViewModel` | `DataType` | column | property:password |
| `RegisterViewModel` | `DataType` | column | property:created_at |
| `RegisterViewModel` | `DataType` | column | property:updated_at |

