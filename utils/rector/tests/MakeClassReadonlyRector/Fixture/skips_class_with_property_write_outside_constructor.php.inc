<?php

class Cache
{
    public function __construct(
        public readonly string $name,
        public readonly array $items,
    ) {}

    public function reset(): void
    {
        $this->items = [];
    }
}

?>
