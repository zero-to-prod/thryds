<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// Helpers
require_once __DIR__ . '/src/Helpers/function.php';

// Core
opcache_compile_file(__DIR__ . '/src/AppEnv.php');
opcache_compile_file(__DIR__ . '/src/Config.php');
opcache_compile_file(__DIR__ . '/src/Log.php');
opcache_compile_file(__DIR__ . '/src/LogLevel.php');

// Helpers
opcache_compile_file(__DIR__ . '/src/Helpers/DataModel.php');
opcache_compile_file(__DIR__ . '/src/Helpers/Describe.php');
opcache_compile_file(__DIR__ . '/src/Helpers/View.php');

// Routes
opcache_compile_file(__DIR__ . '/src/Routes/AboutRoute.php');
opcache_compile_file(__DIR__ . '/src/Routes/HomeRoute.php');
opcache_compile_file(__DIR__ . '/src/Routes/WebRoutes.php');

// ViewModels
opcache_compile_file(__DIR__ . '/src/ViewModels/ErrorViewModel.php');
