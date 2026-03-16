<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Attribute for controlling DataModel property population.
 *
 * Usage: #[Describe([Describe::key => value])]
 *
 * Supported keys:
 * - Describe::default  — default value when the key is missing from input
 * - Describe::cast     — callable(mixed $value, array $context): mixed to transform the value
 * - Describe::from     — alternative key name to read from the input array
 * - Describe::required — bool, throws if the key is missing
 * - Describe::nullable — bool, sets property to null when key is missing
 * - Describe::pre      — callable(s) to run before cast
 * - Describe::post     — callable(s) to run after cast
 * - Describe::via      — method name or callable to resolve the value
 * - Describe::ignore   — bool, skip this property during population
 *
 * @see \Zerotoprod\DataModel\Describe
 */
#[Attribute]
class Describe extends \Zerotoprod\DataModel\Describe {}
