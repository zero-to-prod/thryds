<?php

namespace Fixture;

use Attribute;

#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey = '1. Add constant. 2. Register directive.',
    ) {}
}

// Omits addKey — will have it added.
#[KeyRegistry('vite_entry_points')]
class PageController {}

// Already explicit — untouched.
#[KeyRegistry('vite_entry_points', addKey: 'custom instructions')]
class PostController {}
?>
-----
<?php

namespace Fixture;

use Attribute;

#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey,
    ) {}
}

// Omits addKey — will have it added.
#[KeyRegistry('vite_entry_points', addKey: '1. Add constant. 2. Register directive.')]
class PageController {}

// Already explicit — untouched.
#[KeyRegistry('vite_entry_points', addKey: 'custom instructions')]
class PostController {}
?>
