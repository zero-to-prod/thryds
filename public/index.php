<?php

declare(strict_types=1);
$base_dir = dirname(__DIR__);

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Cache\FileCache;
use League\Route\Cache\Router as CachedRouter;
use League\Route\Http\Exception as HttpException;
use League\Route\Router;
use ZeroToProd\Thryds\APP_ENV;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Helpers\Vite;
use ZeroToProd\Thryds\Log;
use ZeroToProd\Thryds\RequestId;
use ZeroToProd\Thryds\Routes\WebRoutes;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

// Boot phase — runs once per worker
$Config = Config::from([
    Config::APP_ENV => $_SERVER[Config::APP_ENV] ?? $_ENV[Config::APP_ENV] ?? APP_ENV::production->value,
    Config::blade_cache_dir => $base_dir . '/var/cache/blade',
    Config::template_dir => $base_dir . '/templates',
]);

$Container = new BladeContainer();
Container::setInstance(container: $Container);
$Blade = new Blade(viewPaths: $Config->template_dir, cachePath: $Config->blade_cache_dir, container: $Container);

$Blade->if(APP_ENV::production->value, fn(): bool => $Config->APP_ENV === APP_ENV::production);
$Blade->if('env', fn(string ...$environments): bool => in_array($Config->APP_ENV->value, haystack: $environments, strict: true));

$Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
    Vite::app_entry => [Vite::app_css],
]);
$Blade->directive('vite', static fn(): string => $Vite->directivePhp(Vite::app_entry));
$Blade->directive('htmx', static fn(): string => $Vite->directivePhp(Vite::htmx_entry));

$Router = new CachedRouter(
    builder: static function (Router $Router) use ($Blade): Router {
        WebRoutes::register($Router, $Blade);
        return $Router;
    },
    cache: new FileCache(cacheFilePath: $base_dir . '/var/cache/route.cache', ttl: 86400),
    cacheEnabled: $Config->isProduction(),
);
$emit_error_page = static function (string $message, int $status_code) use ($Blade): void {
    new SapiEmitter()->emit(
        response: new HtmlResponse(
            html: $Blade->make(view: View::error, data: [
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
$handler = static function () use ($Router, $Config, $emit_error_page): void {
    $ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

    try {
        new SapiEmitter()->emit(response: $Router->dispatch(request: $ServerRequestInterface)->withHeader(RequestId::header, value: RequestId::init($ServerRequestInterface)));
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
            $Config->isProduction() ? 'Internal Server Error' : $Throwable->getMessage(),
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
