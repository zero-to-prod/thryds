<?php

declare(strict_types=1);
$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception as HttpException;
use League\Route\Router;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Log;
use ZeroToProd\Thryds\Routes\WebRoutes;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

$Config = Config::from([
    Config::appEnv => $_ENV[Config::APP_ENV] ?? AppEnv::production->value,
    Config::bladeCacheDir => $baseDir . '/var/cache/blade',
    Config::templateDir => $baseDir . '/templates',
]);

$Container = new BladeContainer();
Container::setInstance(container: $Container);
$Blade = new Blade(viewPaths: $Config->templateDir, cachePath: $Config->bladeCacheDir, container: $Container);

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

WebRoutes::register(Router: $Router, Blade: $Blade);

try {
    new SapiEmitter()->emit(response: $Router->dispatch(request: $ServerRequestInterface));
} catch (HttpException $HttpException) {
    new SapiEmitter()->emit(
        response: new HtmlResponse(
            html: $Blade->make(view: View::error, data: [
                class_basename(ErrorViewModel::class) => ErrorViewModel::from([
                    ErrorViewModel::message => $HttpException->getMessage(),
                    ErrorViewModel::status_code => $HttpException->getStatusCode(),
                ]),
            ])->render(),
            status: $HttpException->getStatusCode(),
        )
    );
} catch (Throwable $Throwable) {
    Log::error($Throwable->getMessage(), [
        Log::event => Log::unhandled_exception,
        Log::exception => $Throwable::class,
        Log::file => $Throwable->getFile(),
        Log::line => $Throwable->getLine(),
    ]);
    new SapiEmitter()->emit(
        response: new HtmlResponse(
            html: $Blade->make(view: View::error, data: [
                class_basename(ErrorViewModel::class) => ErrorViewModel::from(
                    [
                        ErrorViewModel::status_code => 500,
                        ErrorViewModel::message => $Config->isProduction() ? 'Internal Server Error' : $Throwable->getMessage(),
                    ]
                ),
            ])->render(),
            status: 500,
        )
    );
}
