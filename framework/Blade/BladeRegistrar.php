<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade;

use Attribute;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Marks a class as a Blade directive registrar.
 *
 * Each class carrying this attribute handles registering a single Blade directive
 * (e.g. conditional check, asset injection, runtime snippet).
 * Referenced by the resolution target attribute on BladeDirective enum cases.
 *
 * Classes carrying this attribute must declare register(string $name, Blade $Blade, Config $Config, Vite $Vite): void.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class BladeRegistrar {}
