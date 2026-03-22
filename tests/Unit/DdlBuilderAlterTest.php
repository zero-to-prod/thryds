<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ZeroToProd\Thryds\Schema\DdlBuilder;
use ZeroToProd\Thryds\Schema\Driver;
use ZeroToProd\Thryds\Tables\User;

final class DdlBuilderAlterTest extends TestCase
{
    private static ReflectionClass $ReflectionClass;

    public static function setUpBeforeClass(): void
    {
        self::$ReflectionClass = new ReflectionClass(User::class);
    }

    #[Test]
    public function addColumnSql_generates_alter_table_add_column(): void
    {
        $sql = DdlBuilder::addColumnSql(User::class, User::email, Driver::mysql);

        $this->assertStringStartsWith('ALTER TABLE `users` ADD COLUMN', string: $sql);
        $this->assertStringContainsString('`email`', haystack: $sql);
        $this->assertStringContainsString('VARCHAR(255)', haystack: $sql);
        $this->assertStringContainsString('NULL', haystack: $sql);
    }

    #[Test]
    public function dropColumnSql_generates_alter_table_drop_column(): void
    {
        $this->assertSame('ALTER TABLE `users` DROP COLUMN `email`', DdlBuilder::dropColumnSql(User::class, User::email, Driver::mysql));
    }

    #[Test]
    public function addColumnSql_includes_default_and_comment(): void
    {
        $sql = DdlBuilder::addColumnSql(User::class, User::created_at, Driver::mysql);

        $this->assertStringContainsString('TIMESTAMP', haystack: $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', haystack: $sql);
        $this->assertStringContainsString("COMMENT 'Record creation time'", haystack: $sql);
    }

    #[Test]
    public function reflectColumn_throws_for_nonexistent_property(): void
    {
        $this->expectException(ReflectionException::class);

        DdlBuilder::reflectColumn(self::$ReflectionClass, 'nonexistent_prop');
    }
}
