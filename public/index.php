<?php

declare(strict_types=1);
$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception as HttpException;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Log;

$Config = Config::from([
    Config::appEnv => $_ENV[Config::APP_ENV] ?? AppEnv::Production->value,
    Config::bladeCacheDir => $baseDir . '/var/cache/blade',
    Config::templateDir => $baseDir . '/templates',
]);

$Blade = new Blade(viewPaths: $Config->templateDir, cachePath: $Config->bladeCacheDir);

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

$Router->map('GET', '/', fn(): ResponseInterface => new HtmlResponse(html: $Blade->make(view: View::home)->render()));

try {
    $ResponseInterface = $Router->dispatch(request: $ServerRequestInterface);
} catch (HttpException $HttpException) {
    $HtmlResponse = new HtmlResponse(
        html: $Blade->make(view: View::error, data: [
            // TODO: [AI] Replace magic string 'status_code' with a class constant or enum. Define a public const on the appropriate class with value 'status_code', then reference it here.
            'status_code' => $HttpException->getStatusCode(),
            // TODO: [AI] Replace magic string 'message' with a class constant or enum. Define a public const on the appropriate class with value 'message', then reference it here.
            'message' => $HttpException->getMessage(),
        ])->render(),
        status: $HttpException->getStatusCode(),
    );
} catch (Throwable $Throwable) {
    Log::error($Throwable->getMessage(), [// TODO: [AI] Replace magic string 'exception' with a class constant or enum. Define a public const on the appropriate class with value 'exception', then reference it here.
    'exception' => $Throwable::class, // TODO: [AI] Replace magic string 'file' with a class constant or enum. Define a public const on the appropriate class with value 'file', then reference it here.
    'file' => $Throwable->getFile(), // TODO: [AI] Replace magic string 'line' with a class constant or enum. Define a public const on the appropriate class with value 'line', then reference it here.
    'line' => $Throwable->getLine()]);
    $HtmlResponse = new HtmlResponse(
        html: $Blade->make(view: View::error, data: [
            // TODO: [AI] Replace magic string 'status_code' with a class constant or enum. Define a public const on the appropriate class with value 'status_code', then reference it here.
            'status_code' => 500,
            // TODO: [AI] Replace magic string 'message' with a class constant or enum. Define a public const on the appropriate class with value 'message', then reference it here.
            'message' => $Config->isProduction ? 'Internal Server Error' : $Throwable->getMessage(),
        ])->render(),
        status: 500,
    );
}

new SapiEmitter()->emit(response: $ResponseInterface);
