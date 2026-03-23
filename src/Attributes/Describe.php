<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Attribute for controlling DataModel property population.
 *
 * Supported keys: default, cast, from, required, nullable, pre, post, via, ignore.
 *
 * @see \Zerotoprod\DataModel\Describe
 */
#[Attribute]
#[HopWeight(0)]
class Describe extends \Zerotoprod\DataModel\Describe {}
