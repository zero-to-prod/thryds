<?php


declare(strict_types=1);


// Autoload
opcache_compile_file(__DIR__ . '/vendor/autoload.php');

// Helpers
opcache_compile_file(__DIR__ . '/src/Helpers/function.php');
opcache_compile_file(__DIR__ . '/src/Helpers/DataModel.php');
opcache_compile_file(__DIR__ . '/src/Helpers/View.php');

// Core
opcache_compile_file(__DIR__ . '/src/Config.php');
opcache_compile_file(__DIR__ . '/src/AppEnv.php');

// Routes
opcache_compile_file(__DIR__ . '/src/Routes/WebRoutes.php');
opcache_compile_file(__DIR__ . '/src/Routes/HomeRoute.php');
opcache_compile_file(__DIR__ . '/src/Routes/AboutRoute.php');
opcache_compile_file(__DIR__ . '/src/Routes/OpcacheStatusRoute.php');

// ViewModels
opcache_compile_file(__DIR__ . '/src/ViewModels/ErrorViewModel.php');

// Entrypoint
opcache_compile_file(__DIR__ . '/public/index.php');

// Vendor
opcache_compile_file(__DIR__ . '/vendor/composer/autoload_real.php');
opcache_compile_file(__DIR__ . '/vendor/composer/platform_check.php');
opcache_compile_file(__DIR__ . '/vendor/composer/ClassLoader.php');
opcache_compile_file(__DIR__ . '/vendor/composer/autoload_static.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-mbstring/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-mbstring/bootstrap80.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/collections/functions.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/collections/helpers.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/clock/Resources/now.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/translation/Resources/functions.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/functions.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/helpers.php');
opcache_compile_file(__DIR__ . '/vendor/react/promise/src/functions_include.php');
opcache_compile_file(__DIR__ . '/vendor/react/promise/src/functions.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/deprecation-contracts/function.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-ctype/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-ctype/bootstrap80.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-intl-grapheme/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-intl-grapheme/bootstrap80.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-intl-normalizer/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-intl-normalizer/bootstrap80.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/events/functions.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/filesystem/functions.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/string/Resources/functions.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/functions.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-php80/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-php81/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/symfony/polyfill-php84/bootstrap.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/create_uploaded_file.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/marshal_headers_from_sapi.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/marshal_method_from_sapi.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/marshal_protocol_version_from_sapi.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/normalize_server.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/normalize_uploaded_files.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/functions/parse_cookie_header.php');
opcache_compile_file(__DIR__ . '/vendor/zero-to-prod/data-model/src/DataModel.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/container/Container.php');
opcache_compile_file(__DIR__ . '/vendor/jenssegers/blade/src/Container.php');
opcache_compile_file(__DIR__ . '/vendor/psr/container/src/ContainerInterface.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/Container/Container.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/View/Factory.php');
opcache_compile_file(__DIR__ . '/vendor/jenssegers/blade/src/Blade.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/Facades/Facade.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/ServiceProvider.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/ViewServiceProvider.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Engines/EngineResolver.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/ViewFinderInterface.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/FileViewFinder.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/conditionable/Traits/Conditionable.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/filesystem/Filesystem.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/macroable/Traits/Macroable.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/config/Repository.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/Config/Repository.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/collections/Arr.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/Events/Dispatcher.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/events/Dispatcher.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/Traits/ReflectsClosures.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Factory.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesComponents.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesEvents.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesFragments.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesLayouts.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesLoops.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesStacks.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Concerns/ManagesTranslations.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Compiler.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesAuthorizations.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/BladeCompiler.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesClasses.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesComments.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesComponents.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesConditionals.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesEchos.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesErrors.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesFragments.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesHelpers.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesIncludes.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesInjections.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesJson.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesJs.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesLayouts.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesLoops.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesRawPhp.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesSessions.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesStacks.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesStyles.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesTranslations.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/Concerns/CompilesUseStatements.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Compilers/CompilerInterface.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/RouteCollectionTrait.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/RouteConditionHandlerTrait.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Router.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Middleware/MiddlewareAwareTrait.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Strategy/StrategyAwareTrait.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Middleware/MiddlewareAwareInterface.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/RouteCollectionInterface.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Strategy/StrategyAwareInterface.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-server-handler/src/RequestHandlerInterface.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/RouteConditionHandlerInterface.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/RouteCollector.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/RouteParser.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/RouteParser/Std.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/DataGenerator.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/DataGenerator/RegexBasedAbstract.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/DataGenerator/GroupCountBased.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Route.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-server-middleware/src/MiddlewareInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laravel/serializable-closure/src/SerializableClosure.php');
opcache_compile_file(__DIR__ . '/vendor/laravel/serializable-closure/src/Contracts/Serializable.php');
opcache_compile_file(__DIR__ . '/vendor/laravel/serializable-closure/src/Serializers/Signed.php');
opcache_compile_file(__DIR__ . '/vendor/laravel/serializable-closure/src/Serializers/Native.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/ViewName.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/View.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/Support/Htmlable.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/Support/Renderable.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/View/View.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/contracts/View/Engine.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Engines/PhpEngine.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/view/Engines/CompilerEngine.php');
opcache_compile_file(__DIR__ . '/vendor/illuminate/support/Str.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-factory/src/ServerRequestFactoryInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/ServerRequestFactory.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/ServerRequestFilter/FilterServerRequestInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/ServerRequestFilter/FilterUsingXForwardedHeaders.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/MessageInterface.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/RequestInterface.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/ServerRequestInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/MessageTrait.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/RequestTrait.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/ServerRequest.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-factory/src/UriFactoryInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/UriFactory.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/UriInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Uri.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/StreamInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Stream.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Strategy/StrategyInterface.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Strategy/AbstractStrategy.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Strategy/ApplicationStrategy.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/ContainerAwareTrait.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/ContainerAwareInterface.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/Dispatcher.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/Dispatcher/RegexBasedAbstract.php');
opcache_compile_file(__DIR__ . '/vendor/nikic/fast-route/src/Dispatcher/GroupCountBased.php');
opcache_compile_file(__DIR__ . '/vendor/league/route/src/Dispatcher.php');
opcache_compile_file(__DIR__ . '/vendor/psr/http-message/src/ResponseInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Response.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Response/HtmlResponse.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Response/InjectContentTypeTrait.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/HeaderSecurity.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-httphandlerrunner/src/Emitter/EmitterInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-httphandlerrunner/src/Emitter/SapiEmitterTrait.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-httphandlerrunner/src/Emitter/SapiEmitter.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-httphandlerrunner/src/Exception/EmitterException.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-httphandlerrunner/src/Exception/ExceptionInterface.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/Response/JsonResponse.php');
opcache_compile_file(__DIR__ . '/vendor/laminas/laminas-diactoros/src/ServerRequestFilter/IPRange.php');
