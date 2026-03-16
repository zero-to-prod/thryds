<?php

namespace Fixture;

function processItems(array $items): void
{
    foreach ($items as $item) {
        if ($item->isActive()) {
            if ($item->isValid()) {
                if ($item->needsProcessing()) {
                    handle($item);
                }
            }
        }
    }
}

?>
-----
<?php

namespace Fixture;

function processItems(array $items): void
{
    foreach ($items as $item) {
        if (!$item->isActive()) {
            continue;
        }
        if (!$item->isValid()) {
            continue;
        }
        if (!$item->needsProcessing()) {
            continue;
        }
        handle($item);
    }
}

?>
