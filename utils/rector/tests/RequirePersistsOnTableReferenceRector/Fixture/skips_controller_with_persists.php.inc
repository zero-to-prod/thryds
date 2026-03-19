<?php

namespace TestControllers;

use TestTables\User;

#[Persists(User::class)]
class RegisterController
{
    public function __invoke(): void {}
}
