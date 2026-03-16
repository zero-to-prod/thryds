<?php

declare(strict_types=1);

// Simulated route registrations used by the test fixtures.
// The scanDir in configured_rule.php points here.

$Router->map('GET', TestRoute::home->value, $handler);
$Router->map('GET', TestRoute::about->value, $handler);
