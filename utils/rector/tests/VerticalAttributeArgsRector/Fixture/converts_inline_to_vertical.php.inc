<?php

#[Foo(a: 1, b: 2)]
enum Bar: string
{
    case x = 'x';
}

?>
-----
<?php

#[Foo(
    a: 1,
    b: 2
)]
enum Bar: string
{
    case x = 'x';
}

?>
