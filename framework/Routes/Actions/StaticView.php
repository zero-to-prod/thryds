<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes\Actions;

use BackedEnum;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Routes\HttpMethod;

/** Render a Blade view with no controller. */
#[ActionStrategy]
#[Infrastructure]
final readonly class StaticView implements Stringable
{
    public function __construct(
        public BackedEnum $BackedEnum,
    ) {}

    public function toCallable(BackedEnum $BackedEnum, HttpMethod $HttpMethod): callable
    {
        return fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: (string) $this->BackedEnum->value)->render(),
        );
    }

    public function __toString(): string
    {
        return 'StaticView';
    }
}
