<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ZeroToProd\Thryds\Schema\DdlBuilder;
use ZeroToProd\Thryds\Tables\User;

final class DdlBuilderAlterTest extends TestCase
{
    #[Test]
    public function addColumnSql_generates_alter_table_add_column(): void
    {
        $sql = DdlBuilder::addColumnSql(User::class, User::email);

        $this->assertStringStartsWith('ALTER TABLE `users` ADD COLUMN', string: $sql);
        $this->assertStringContainsString('`email`', haystack: $sql);
        $this->assertStringContainsString('VARCHAR(255)', haystack: $sql);
        $this->assertStringContainsString('NULL', haystack: $sql);
    }

    #[Test]
    public function dropColumnSql_generates_alter_table_drop_column(): void
    {
        $this->assertSame('ALTER TABLE `users` DROP COLUMN `email`', DdlBuilder::dropColumnSql(User::class, User::email));
    }

    #[Test]
    public function addColumnSql_includes_default_and_comment(): void
    {
        $sql = DdlBuilder::addColumnSql(User::class, User::created_at);

        $this->assertStringContainsString('TIMESTAMP', haystack: $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', haystack: $sql);
        $this->assertStringContainsString("COMMENT 'Record creation time'", haystack: $sql);
    }

    #[Test]
    public function reflectColumn_throws_for_nonexistent_property(): void
    {
        $this->expectException(ReflectionException::class);

        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        DdlBuilder::reflectColumn(new ReflectionClass(User::class), 'nonexistent_prop');
    }
}
