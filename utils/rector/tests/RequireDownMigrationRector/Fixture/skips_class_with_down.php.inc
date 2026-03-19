<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration2
{
    public function __construct(public string $id, public string $description) {}
}

#[Migration2(id: '0002', description: 'Add email index')]
final class AddEmailIndex
{
    public function up(): void {}

    public function down(): void {}
}
