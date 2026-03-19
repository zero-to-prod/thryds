<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration
{
    public function __construct(public string $id, public string $description) {}
}

#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable
{
    public function up(): void {}
}

?>
-----
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration
{
    public function __construct(public string $id, public string $description) {}
}

// TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable
{
    public function up(): void {}
}

?>
