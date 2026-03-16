<?php

declare(strict_types=1);
$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception as HttpException;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Log;

$Config = Config::from([
    Config::appEnv => $_ENV[Config::APP_ENV] ?? AppEnv::Production->value,
    Config::twigCacheDir => $baseDir . '/var/cache/twig',
    Config::templateDir => $baseDir . '/templates',
]);

$FilesystemLoader = new FilesystemLoader(paths: $Config->templateDir);
$Environment = new Environment(loader: $FilesystemLoader, options: [
    Config::TWIG_CACHE => $Config->twigCacheDir,
    Config::TWIG_AUTO_RELOAD => !$Config->isProduction,
]);

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

$Router->map('GET', '/', fn(): ResponseInterface => new HtmlResponse(html: $Environment->render(name: 'home.html.twig')));

try {
    $ResponseInterface = $Router->dispatch(request: $ServerRequestInterface);
} catch (HttpException $HttpException) {
    $ResponseInterface = new HtmlResponse(
        html: $Environment->render(name: 'error.html.twig', context: [
            'status_code' => $HttpException->getStatusCode(),
            'message' => $HttpException->getMessage(),
        ]),
        status: $HttpException->getStatusCode(),
    );
} catch (\Throwable $Throwable) {
    Log::error($Throwable->getMessage(), ['exception' => $Throwable::class, 'file' => $Throwable->getFile(), 'line' => $Throwable->getLine()]);
    $ResponseInterface = new HtmlResponse(
        html: $Environment->render(name: 'error.html.twig', context: [
            'status_code' => 500,
            'message' => $Config->isProduction ? 'Internal Server Error' : $Throwable->getMessage(),
        ]),
        status: 500,
    );
}

new SapiEmitter()->emit(response: $ResponseInterface);
