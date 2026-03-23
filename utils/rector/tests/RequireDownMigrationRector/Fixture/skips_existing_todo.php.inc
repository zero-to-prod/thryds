<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration3
{
    public function __construct(public string $id, public string $description) {}
}

// TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration3(id: '0003', description: 'Drop old table')]
final class DropOldTable {}
