<?php

use Zerotoprod\DataModel\DataModel;

class OrderItem
{
    use DataModel;

    public string $name;
    public int $quantity;
}

$OrderItem = OrderItem::from([
    'name' => 'Widget',
    'quantity' => 3,
]);

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;

class OrderItem
{
    use DataModel;
    /** @see $name */
    public const string name = 'name';
    /** @see $quantity */
    public const string quantity = 'quantity';

    public string $name;
    public int $quantity;
}

$OrderItem = OrderItem::from([
    \OrderItem::name => 'Widget',
    \OrderItem::quantity => 3,
]);

?>
