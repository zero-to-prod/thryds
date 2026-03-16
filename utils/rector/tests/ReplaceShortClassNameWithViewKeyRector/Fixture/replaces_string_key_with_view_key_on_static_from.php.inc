<?php

class ViewModelWithKey
{
    public const string view_key = 'ViewModelWithKey';
    public const string message = 'message';

    public static function from(array $data): self
    {
        return new self();
    }
}

$data = [
    'model' => ViewModelWithKey::from([
        ViewModelWithKey::message => 'test',
    ]),
];

?>
-----
<?php

class ViewModelWithKey
{
    public const string view_key = 'ViewModelWithKey';
    public const string message = 'message';

    public static function from(array $data): self
    {
        return new self();
    }
}

$data = [
    \ViewModelWithKey::view_key => ViewModelWithKey::from([
        ViewModelWithKey::message => 'test',
    ]),
];

?>
