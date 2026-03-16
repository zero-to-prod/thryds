<?php

$Router->map('GET', Route::home->value, $handlerA);
// TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: 'GET home' was already registered above.
$Router->map('GET', Route::home->value, $handlerB);
