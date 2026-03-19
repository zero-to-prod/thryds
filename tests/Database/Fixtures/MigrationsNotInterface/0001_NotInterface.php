<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;

// Intentionally does not implement MigrationInterface so that instantiate()
// throws a RuntimeException when migrate() or rollback() tries to use it.
#[Migration(id: '0001', description: 'Not interface fixture')]
final class NotInterface {}
