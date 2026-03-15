<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;

$ServerRequestInterface = ServerRequestFactory::fromGlobals(server: $_SERVER, query: $_GET, body: $_POST, cookies: $_COOKIE, files: $_FILES);

$Router = new Router();

$Router->map('GET', '/', fn(): ResponseInterface => new HtmlResponse('<html><head><title>Thryds</title></head><body><h1>Thryds</h1></body></html>'));

$ResponseInterface = $Router->dispatch(request: $ServerRequestInterface);

new SapiEmitter()->emit(response: $ResponseInterface);
