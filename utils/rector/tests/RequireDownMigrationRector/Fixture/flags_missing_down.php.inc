<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration
{
    public function __construct(public string $id, public string $description) {}
}

#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable {}

?>
-----
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration
{
    public function __construct(public string $id, public string $description) {}
}

// TODO: [RequireDownMigrationRector] Migration class has no MigrationAction attribute — add #[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql]. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable {}

?>
