<?php

#[ClosedSet(Domain::blade_components, addCase: <<<TEXT
    1. Add enum case.
    2. Create templates/components/{case}.blade.php with @props.
    3. Add example to styleguide template.
    TEXT)]
enum Foo: string
{
    case bar = 'bar';
}
?>
-----
<?php

#[ClosedSet(Domain::blade_components, addCase: <<<TEXT
    1. Add enum case.
    2. Create templates/components/{case}.blade.php with @props.
    3. Add example to styleguide template.
    TEXT)]
enum Foo: string
{
    case bar = 'bar';
}
?>
