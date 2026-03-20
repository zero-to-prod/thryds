<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\SuggestEnumForStringPropertyRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class SuggestEnumForStringPropertyExcludedFileRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/ExcludedFixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/excluded_config/configured_rule.php';
    }
}
