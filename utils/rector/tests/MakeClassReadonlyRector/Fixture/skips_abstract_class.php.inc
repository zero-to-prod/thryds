<?php

abstract class BaseHandler
{
    public function __construct(
        public readonly string $name,
    ) {}

    abstract public function handle(): void;
}

?>
