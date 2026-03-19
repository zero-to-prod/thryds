<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;

// Intentionally declares an id that does not match the filename prefix so
// that discover() throws a RuntimeException on the mismatch.
#[Migration(id: '9999', description: 'Wrong id fixture')]
final class WrongId {}
