<?php

// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/login' to a Route class constant.
$Router->map('POST', '/login', $handler);
