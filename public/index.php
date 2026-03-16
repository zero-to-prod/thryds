<?php

declare(strict_types=1);
$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;

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

$ResponseInterface = $Router->dispatch(request: $ServerRequestInterface);

new SapiEmitter()->emit(response: $ResponseInterface);
