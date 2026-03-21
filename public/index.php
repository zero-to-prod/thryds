<?php

declare(strict_types=1);
// Worker entrypoint: the process boots once and handles requests in a persistent loop.
$base_dir = dirname(__DIR__);

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\Env;
use ZeroToProd\Thryds\Header;
use ZeroToProd\Thryds\RequestId;

// Worker boots once; its in-memory object graph persists for the process lifetime.
// In development, file changes trigger a worker restart that re-initializes this state.
// See HOT-001, PERF-001
$App = App::boot($base_dir);

// Resolved once at boot; forgetCompiledOrNotExpired() is called per-request to
// discard template state accumulated during the request. Must follow App::boot().
$bladeEngine = Container::getInstance()->make('view.engine.resolver')->resolve('blade');

// Request handler — called for each incoming request
$handler = static function () use ($App, $bladeEngine): void {
    $ServerRequestInterface = ServerRequestFactory::fromGlobals(
        server: $_SERVER,
        query: $_GET,
        body: $_POST,
        cookies: $_COOKIE,
        files: $_FILES
    );

    try {
        new SapiEmitter()->emit(
            response: $App->Router->dispatch(request: $ServerRequestInterface)->withHeader(
                Header::request_id,
                value: RequestId::init($ServerRequestInterface)
            )
        );
    } catch (Throwable $Throwable) {
        $App->ExceptionHandler->handle($Throwable);
    } finally {
        // Static state persists across requests in worker mode; both calls below
        // clear per-request state so it does not bleed into the next request.
        // See SEC-001, HOT-007
        RequestId::reset();
        $bladeEngine->forgetCompiledOrNotExpired();
    }
};

// Worker loop: blocks until a request arrives, dispatches to $handler,
// then continues or exits based on the return value.
$max_requests = (int) ($_SERVER[Env::MAX_REQUESTS] ?? 0);
for ($nb_requests = 0; !$max_requests || $nb_requests < $max_requests; ++$nb_requests) {
    $keep_running = frankenphp_handle_request(callback: $handler);

    gc_collect_cycles();

    if (!$keep_running) {
        break;
    }
}
