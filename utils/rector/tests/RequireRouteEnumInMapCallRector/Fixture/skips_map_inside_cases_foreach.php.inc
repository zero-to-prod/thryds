<?php

foreach (ZeroToProd\Thryds\Routes\RouteList::cases() as $Route) {
    $Router->map('GET', $Route->value, $handler);
}
