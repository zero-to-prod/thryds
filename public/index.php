<?php

declare(strict_types=1);
$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

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

use function ZeroToProd\Thryds\Helpers\short_class_name;

use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Log;
use ZeroToProd\Thryds\Routes\WebRoutes;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

$Config = Config::from([
    Config::AppEnv => $_ENV[Config::APP_ENV] ?? AppEnv::production->value,
    Config::blade_cache_dir => $base_dir . '/var/cache/blade',
    Config::template_dir => $base_dir . '/templates',
]);

$Container = new BladeContainer();
Container::setInstance(container: $Container);
$Blade = new Blade(viewPaths: $Config->template_dir, cachePath: $Config->blade_cache_dir, container: $Container);

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

WebRoutes::register($Router, $Blade);

$Closure = static function (string $message, int $status_code) use ($Blade): void {
    new SapiEmitter()->emit(
        response: new HtmlResponse(
            html: $Blade->make(view: View::error, data: [
                short_class_name(ErrorViewModel::class) => ErrorViewModel::from([
                    ErrorViewModel::message => $message,
                    ErrorViewModel::status_code => $status_code,
                ]),
            ])->render(),
            status: $status_code,
        )
    );
};

try {
    new SapiEmitter()->emit(response: $Router->dispatch(request: $ServerRequestInterface));
} catch (HttpException $HttpException) {
    $Closure($HttpException->getMessage(), $HttpException->getStatusCode());
} catch (Throwable $Throwable) {
    Log::error($Throwable->getMessage(), [
        Log::event => Log::unhandled_exception,
        Log::exception => $Throwable::class,
        Log::file => $Throwable->getFile(),
        Log::line => $Throwable->getLine(),
    ]);
    $Closure(
        $Config->isProduction() ? 'Internal Server Error' : $Throwable->getMessage(),
        500,
    );
}
