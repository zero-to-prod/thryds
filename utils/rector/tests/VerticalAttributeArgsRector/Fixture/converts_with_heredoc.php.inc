<?php

#[ClosedSet(Domain::foo, addCase: <<<TEXT
    1. Add enum case.
    2. Do something.
TEXT)]
enum Foo: string
{
    case x = 'x';
}

?>
-----
<?php

#[ClosedSet(
    Domain::foo,
    addCase: <<<TEXT
    1. Add enum case.
    2. Do something.
    TEXT
)]
enum Foo: string
{
    case x = 'x';
}

?>
