<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration2
{
    public function __construct(public string $id, public string $description) {}
}

#[\Attribute(\Attribute::TARGET_CLASS)]
class RawSql
{
    public function __construct(public string $up, public string $down) {}
}

#[Migration2(id: '0002', description: 'Add email index')]
#[RawSql(up: 'CREATE INDEX idx_email ON users (email)', down: 'DROP INDEX idx_email ON users')]
final class AddEmailIndex {}
