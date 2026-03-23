<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes\Actions;

use BackedEnum;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Requests\InputField;
use ZeroToProd\Framework\Routes\HttpMethod;

/** Render a form view with an empty ViewModel. */
#[ActionStrategy]
#[Infrastructure]
final readonly class Form implements Stringable
{
    /**
     * @param class-string $controller
     * @param class-string $request
     * @param class-string $view_model
     */
    public function __construct(
        public BackedEnum $BackedEnum,
        public string $controller,
        public string $request,
        public string $view_model,
    ) {}

    public function toCallable(BackedEnum $BackedEnum, HttpMethod $HttpMethod): callable
    {
        return fn(): ResponseInterface => $this->render(data: []);
    }

    /** @param array<string, mixed> $data */
    public function render(array $data): HtmlResponse
    {
        $view_model_class = $this->view_model;

        return new HtmlResponse(
            html: blade()->make(view: (string) $this->BackedEnum->value, data: [
                $view_model_class::view_key => $view_model_class::from($data),
                InputField::fields => InputField::reflect(class: $this->request),
            ])->render()
        );
    }

    public function __toString(): string
    {
        return 'Form';
    }
}
