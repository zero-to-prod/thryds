<?php

enum Layer: string
{
    case controllers = 'controllers';
    case schema = 'schema';
}
?>
-----
<?php

// TODO: [EnforceLayerCoverageRector] Namespace segment "Queries" has no corresponding Layer enum case — add one to ensure attribute graph visibility.
enum Layer: string
{
    case controllers = 'controllers';
    case schema = 'schema';
}
?>
