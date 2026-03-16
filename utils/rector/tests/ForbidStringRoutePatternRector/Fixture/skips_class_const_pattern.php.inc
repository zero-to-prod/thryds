<?php

$Router->map('GET', HomeRoute::pattern, $handler);
$Router->map('GET', PostRoute::SHOW, $handler);
