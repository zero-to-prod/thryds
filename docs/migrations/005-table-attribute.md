# Phase 5: #[Table] Attribute — Optional Engine/Charset/Collation

## Goal

Make `Engine`, `Charset`, and `Collation` nullable on the `#[Table]` attribute so that PostgreSQL and SQLite table definitions do not require MySQL-specific parameters.

## Current State

```php
// src/Attributes/Table.php
public function __construct(
    public TableName $TableName,
    public Engine $Engine,
    public Charset $Charset,
    public Collation $Collation,
) {}
```

All four parameters are required. Every `#[Table]` declaration must specify `Engine::InnoDB`, `Charset::utf8mb4`, `Collation::utf8mb4_unicode_ci` even when targeting PostgreSQL or SQLite.

## Target State

```php
public function __construct(
    public TableName $TableName,
    public ?Engine $Engine = null,
    public ?Charset $Charset = null,
    public ?Collation $Collation = null,
) {}
```

## Impact on Existing Code

### Table Models

```php
// Before — src/Tables/Migration.php
#[Table(
    TableName: TableName::migrations,
    Engine: Engine::InnoDB,
    Charset: Charset::utf8mb4,
    Collation: Collation::utf8mb4_unicode_ci
)]

// After — still valid, unchanged
#[Table(
    TableName: TableName::migrations,
    Engine: Engine::InnoDB,
    Charset: Charset::utf8mb4,
    Collation: Collation::utf8mb4_unicode_ci
)]

// Also valid — for driver-agnostic tables
#[Table(TableName: TableName::migrations)]
```

Existing MySQL declarations continue to work with no changes. The parameters become optional, not removed.

### DdlBuilder (Phase 4 prerequisite)

`Driver::tableOptions()` already handles nullable parameters:

```php
public function tableOptions(?Engine $Engine, ?Charset $Charset, ?Collation $Collation): string
{
    return match ($this) {
        self::mysql => ($Engine !== null ? ' ENGINE=' . $Engine->value : '')
            . ($Charset !== null ? ' DEFAULT CHARSET=' . $Charset->value : '')
            . ($Collation !== null ? ' COLLATE=' . $Collation->value : ''),
        self::pgsql, self::sqlite => '',
    };
}
```

When targeting MySQL with null Engine/Charset/Collation, MySQL uses its server defaults (InnoDB, utf8mb4 on modern versions). This is acceptable — explicit is better but not required.

### Enum Docblocks

Update `Engine`, `Charset`, and `Collation` enum docblocks to clarify they are MySQL/MariaDB concepts:

```php
// src/Schema/Engine.php
/**
 * Closed set of supported MySQL/MariaDB storage engines.
 *
 * Used in #[Table] when targeting MySQL/MariaDB. Ignored by PostgreSQL and SQLite.
 * The backed string value is used directly in the ENGINE= clause of CREATE TABLE.
 */

// src/Schema/Charset.php
/**
 * Closed set of supported MySQL/MariaDB character sets.
 *
 * Used in #[Table] when targeting MySQL/MariaDB. Ignored by PostgreSQL and SQLite.
 * The backed string value is used directly in the DEFAULT CHARSET= clause of CREATE TABLE.
 */

// src/Schema/Collation.php
/**
 * Closed set of supported MySQL/MariaDB collations.
 *
 * Used in #[Table] when targeting MySQL/MariaDB. Ignored by PostgreSQL and SQLite.
 * The backed string value is used directly in the COLLATE= clause of CREATE TABLE.
 */
```

## Implementation Steps

### Step 1: Make `#[Table]` constructor parameters nullable with defaults

- `?Engine $Engine = null`
- `?Charset $Charset = null`
- `?Collation $Collation = null`

### Step 2: Update enum docblocks

- `Engine.php` — note MySQL/MariaDB only
- `Charset.php` — note MySQL/MariaDB only
- `Collation.php` — note MySQL/MariaDB only

### Step 3: Run check:all

- Existing `#[Table]` declarations with explicit values still pass
- No runtime behavior changes for MySQL deployments

## Files Modified

| File | Change |
|------|--------|
| `src/Attributes/Table.php` | Make `Engine`, `Charset`, `Collation` nullable with null defaults |
| `src/Schema/Engine.php` | Docblock update — note MySQL/MariaDB only |
| `src/Schema/Charset.php` | Docblock update — note MySQL/MariaDB only |
| `src/Schema/Collation.php` | Docblock update — note MySQL/MariaDB only |
| `src/Schema/SortDirection.php` | Docblock update — remove MySQL-specific note, standard SQL |
