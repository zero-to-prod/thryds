<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use ZeroToProd\Thryds\Config;

$Config = Config::from([
    Config::appEnv => $_ENV['APP_ENV'] ?? 'production',
    Config::twigCacheDir => dirname(__DIR__) . '/var/cache/twig',
    Config::templateDir => dirname(__DIR__) . '/templates',
]);

$FilesystemLoader = new FilesystemLoader(paths: $Config->templateDir);
$Environment = new Environment(loader: $FilesystemLoader, options: [
    'cache' => $Config->twigCacheDir,
    'auto_reload' => !$Config->isProduction,
]);

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

$Router->map('GET', '/', fn(): ResponseInterface => new HtmlResponse(html: $Environment->render(name: 'home.html.twig')));

$ResponseInterface = $Router->dispatch(request: $ServerRequestInterface);

new SapiEmitter()->emit(response: $ResponseInterface);
