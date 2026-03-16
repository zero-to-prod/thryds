<?php

class PlainService
{
    public const string status = 'status';

    public static function from(array $data): self
    {
        return new self();
    }
}

$PlainService = PlainService::from([
    'status' => 'active',
]);

?>
