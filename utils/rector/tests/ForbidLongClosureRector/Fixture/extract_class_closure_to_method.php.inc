<?php

namespace Fixture;

class Handler
{
    public function boot(): void
    {
        $render_error = function (string $message, int $code) use ($Blade): void {
            $html = $Blade->make('error', ['message' => $message])->render();
            (new SapiEmitter())->emit(new HtmlResponse($html, $code));
            log_something();
            finish();
        };
    }
}

?>
-----
<?php

namespace Fixture;

class Handler
{
    public function boot(): void
    {
        $render_error = (fn(string $message, int $code): void => $this->renderError($Blade, $message, $code));
    }
    private function renderError(mixed $Blade, string $message, int $code): void
    {
        $html = $Blade->make('error', ['message' => $message])->render();
        (new SapiEmitter())->emit(new HtmlResponse($html, $code));
        log_something();
        finish();
    }
}

?>
