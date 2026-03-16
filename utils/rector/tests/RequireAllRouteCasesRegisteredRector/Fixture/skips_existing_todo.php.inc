<?php

enum TestRoute: string
{
    case home = '/';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'unregistered' is defined but never registered in any router map() call.
    case unregistered = '/unregistered';
}
