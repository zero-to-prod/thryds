<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration3
{
    public function __construct(public string $id, public string $description) {}
}

// TODO: [RequireDownMigrationRector] Migration class has no MigrationAction attribute — add #[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql]. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration3(id: '0003', description: 'Drop old table')]
final class DropOldTable {}
