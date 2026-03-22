<?php

use ZeroToProd\Thryds\Attributes\Segment;

enum Layer: string
{
    case controllers = 'controllers';
    #[Segment('Queries')]
    case data = 'queries';
    case schema = 'schema';
}
?>
