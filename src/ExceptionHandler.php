<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use League\Route\Http\Exception as HttpException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use ZeroToProd\Thryds\Attributes\HandlesException;
use ZeroToProd\Thryds\Attributes\SourceOfTruth;
use ZeroToProd\Thryds\Attributes\SourceOfTruthConcept;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

#[SourceOfTruth(
    SourceOfTruthConcept::exception_dispatch_methods,
    addCase: <<<TEXT
        1. Add public method with #[HandlesException(ExceptionClass::class)] attribute.
        2. The constructor auto-discovers it via reflection.
    TEXT,
)]
readonly class ExceptionHandler
{
    /** @var list<array{ReflectionMethod, class-string<Throwable>}> */
    private array $handlers;

    public function __construct(
        private Config $Config,
        private EmitterInterface $EmitterInterface,
    ) {
        $handlers = [];
        foreach (new ReflectionClass(self::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $Method) {
            $attributes = $Method->getAttributes(HandlesException::class);
            if ($attributes === []) {
                continue;
            }
            $handlers[] = [$Method, $attributes[0]->newInstance()->exception];
        }
        $this->handlers = $handlers;
    }

    #[HandlesException(HttpException::class)]
    public function handleHttpException(HttpException $Exception): void
    {
        $this->emitErrorPage($Exception->getMessage(), $Exception->getStatusCode());
    }

    #[HandlesException(Throwable::class)]
    public function handleThrowable(Throwable $Throwable): void
    {
        Log::error($Throwable->getMessage(), [
            LogContext::event => LogContext::unhandled_exception,
            LogContext::exception => $Throwable::class,
            LogContext::file => $Throwable->getFile(),
            LogContext::line => $Throwable->getLine(),
        ]);
        $this->emitErrorPage(
            $this->Config->isProduction() ? 'Internal Server Error' : $Throwable->getMessage(),
            500,
        );
    }

    /**
     * Dispatches to the handler method whose #[HandlesException] type is the most
     * specific match for the thrown exception. Falls back to handleThrowable for
     * any unmatched exception type.
     */
    public function handle(Throwable $Throwable): void
    {
        $candidates = $this->candidates($Throwable);

        if ($candidates !== []) {
            $candidates[0][0]->invoke($this, $Throwable);
        }
    }

    /**
     * Filters pre-resolved handlers to find those matching the given exception,
     * sorted most-specific first.
     *
     * @return list<array{ReflectionMethod, class-string<Throwable>}>
     */
    private function candidates(Throwable $Throwable): array
    {
        $matches = [];

        foreach ($this->handlers as [$Method, $exception_class]) {
            if ($Throwable instanceof $exception_class) {
                $matches[] = [$Method, $exception_class];
            }
        }

        usort(array: $matches, callback: static fn(array $a, array $b): int => is_subclass_of(object_or_class: $a[1], class: $b[1]) ? -1 : 1);

        return $matches;
    }

    private function emitErrorPage(string $message, int $status_code): void
    {
        $this->EmitterInterface->emit(
            response: new HtmlResponse(
                html: blade()->make(view: View::error->value, data: [
                    ErrorViewModel::view_key => ErrorViewModel::from([
                        ErrorViewModel::message => $message,
                        ErrorViewModel::status_code => $status_code,
                    ]),
                ])->render(),
                status: $status_code,
            )
        );
    }
}
