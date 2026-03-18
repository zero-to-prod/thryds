<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

/**
 * Project alias for {@see \Zerotoprod\DataModel\DataModel}.
 *
 * Provides `static from(array|object|null|string $context = []): self` — populates
 * public properties from array keys matching property names.
 *
 * Use the {@see Describe} attribute on properties to control population:
 * - `default`  — value when the key is missing (may be callable)
 * - `cast`     — custom casting function
 * - `from`     — map a different key name to this property
 * - `required` — throw if key is missing
 * - `nullable` — set to null if missing
 * - `pre`/`post` — hooks before/after casting
 * - `via`      — alternative instantiation method (default `from`)
 * - `ignore`   — skip the property entirely
 */
trait DataModel
{
    use \Zerotoprod\DataModel\DataModel;
}
