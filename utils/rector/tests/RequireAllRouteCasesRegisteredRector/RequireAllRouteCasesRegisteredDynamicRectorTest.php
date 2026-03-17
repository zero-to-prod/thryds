<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireAllRouteCasesRegisteredRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RequireAllRouteCasesRegisteredDynamicRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/DynamicFixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/dynamic_config/configured_rule.php';
    }
}
