<?php

declare(strict_types=1);
$base_dir = dirname(__DIR__);

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception as HttpException;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Log;
use ZeroToProd\Thryds\RequestId;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

$App = App::boot($base_dir);

$emit_error_page = static function (string $message, int $status_code) use ($App): void {
    new SapiEmitter()->emit(
        response: new HtmlResponse(
            html: $App->Blade->make(view: View::error, data: [
                ErrorViewModel::view_key => ErrorViewModel::from([
                    ErrorViewModel::message => $message,
                    ErrorViewModel::status_code => $status_code,
                ]),
            ])->render(),
            status: $status_code,
        )
    );
};

// Request handler — called for each incoming request
$handler = static function () use ($App, $emit_error_page): void {
    $ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

    try {
        new SapiEmitter()->emit(response: $App->Router->dispatch(request: $ServerRequestInterface)->withHeader(RequestId::header, value: RequestId::init($ServerRequestInterface)));
    } catch (HttpException $HttpException) {
        $emit_error_page($HttpException->getMessage(), $HttpException->getStatusCode());
    } catch (Throwable $Throwable) {
        Log::error($Throwable->getMessage(), [
            Log::event => Log::unhandled_exception,
            Log::exception => $Throwable::class,
            Log::file => $Throwable->getFile(),
            Log::line => $Throwable->getLine(),
        ]);
        $emit_error_page(
            $App->Config->isProduction() ? 'Internal Server Error' : $Throwable->getMessage(),
            500,
        );
    } finally {
        RequestId::reset();
    }
};

// FrankenPHP worker loop: frankenphp_handle_request() blocks until a request arrives,
// invokes $handler, then returns true to continue or false to stop the worker.
$max_requests = (int) ($_SERVER[Config::MAX_REQUESTS] ?? 0);
for ($nb_requests = 0; !$max_requests || $nb_requests < $max_requests; ++$nb_requests) {
    $keep_running = frankenphp_handle_request(callback: $handler);

    gc_collect_cycles();

    if (!$keep_running) {
        break;
    }
}
