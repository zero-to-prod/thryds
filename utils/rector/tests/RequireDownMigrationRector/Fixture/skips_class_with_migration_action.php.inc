<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Migration4
{
    public function __construct(public string $id, public string $description) {}
}

#[\Attribute(\Attribute::TARGET_CLASS)]
class CreateTable
{
    public function __construct(public string $table) {}
}

#[Migration4(id: '0004', description: 'Create posts table')]
#[CreateTable(table: 'posts')]
final class CreatePostsTable {}
