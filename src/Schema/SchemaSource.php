<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::schema_sync_sources,
    addCase: 'Add enum case. Then handle in sync-schema.php sync dispatch.'
)]
/**
 * Declares which system is authoritative for a table's column definitions.
 *
 * Used by #[SchemaSync] on Table classes to control sync:schema behavior.
 */
enum SchemaSource: string
{
    /** The live database is authoritative — sync PHP attributes from DB. */
    case database = 'database';

    /** PHP attributes are authoritative — report drift, do not mutate PHP files. */
    case attributes = 'attributes';
}
