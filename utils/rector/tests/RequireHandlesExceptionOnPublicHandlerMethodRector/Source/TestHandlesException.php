<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source;

#[\Attribute(\Attribute::TARGET_METHOD)]
readonly class TestHandlesException
{
    /** @param class-string<\Throwable> $exception */
    public function __construct(public string $exception) {}
}
