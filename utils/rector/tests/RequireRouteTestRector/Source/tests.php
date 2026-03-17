<?php

declare(strict_types=1);

// Simulated test file used by the test fixtures.
// The testDir in configured_rule.php points here.
// Only 'home' and 'about' are referenced, so 'untested' should be flagged.

$this->get(TestRoute::home);
$this->get(TestRoute::about);
