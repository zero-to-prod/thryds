<?php

$Router->map('GET', ZeroToProd\Thryds\Routes\RouteList::home->value, $handler);
$Router->map('POST', ZeroToProd\Thryds\Routes\RouteList::about->value, $handler);
