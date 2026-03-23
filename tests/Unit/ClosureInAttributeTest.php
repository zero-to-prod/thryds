<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClosureInAttributeTest extends TestCase
{
    /** Lint a PHP code string and return the combined stdout+stderr output. */
    private function lint(string $code): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'closure_attr_') . '.php';
        file_put_contents(filename: $tmp, data: $code);

        exec(
            command: PHP_BINARY . ' --no-php-ini -l ' . escapeshellarg(arg: $tmp) . ' 2>&1',
            output: $output,
        );

        unlink(filename: $tmp);

        return implode("\n", array: $output);
    }

    #[Test]
    public function arrowFunctionInAttributeIsRejected(): void
    {
        $this->assertStringContainsString(
            'Constant expression contains invalid operations',
            $this->lint(<<<'PHP'
            <?php
        #[\Attribute]
        readonly class Tag {
            public function __construct(public \Closure $fn) {}
        }

        enum Routes: string
        {
            #[Tag(static fn(): string => 'hello')]
            case ping = '/ping';
        }
        PHP),
        );
    }

    #[Test]
    public function staticClosureInAttributeIsAccepted(): void
    {
        $this->assertStringContainsString(
            'No syntax errors detected',
            $this->lint(<<<'PHP'
            <?php
        #[\Attribute]
        readonly class Tag {
            public function __construct(public \Closure $fn) {}
        }

        enum Routes: string
        {
            #[Tag(static function(): string { return 'hello'; })]
            case ping = '/ping';
        }
        PHP),
        );
    }
}
