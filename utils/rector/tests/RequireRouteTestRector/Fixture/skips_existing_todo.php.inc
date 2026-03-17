<?php

enum TestRoute: string
{
    case home = '/';
    // TODO: [RequireRouteTestRector] Route case 'untested' has no corresponding test. Add a test that exercises this route.
    case untested = '/untested';
}
