<?php


class SomeViewModel
{
    public const string view_key = 'SomeViewModel';
}

$data = [
    short_class_name(SomeViewModel::class) => new SomeViewModel(),
];

?>
-----
<?php


class SomeViewModel
{
    public const string view_key = 'SomeViewModel';
}

$data = [
    \SomeViewModel::view_key => new SomeViewModel(),
];

?>
