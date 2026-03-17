<?php

declare(strict_types=1);

foreach (TestRoute::cases() as $Route) {
    $Router->map('GET', $Route->value, $handler);
}
