<?php

foreach (ZeroToProd\Thryds\Routes\Route::cases() as $Route) {
    $Router->map('GET', $Route->value, $handler);
}
