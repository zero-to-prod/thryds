<?php

class UserDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

?>
-----
<?php

readonly class UserDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}

?>
