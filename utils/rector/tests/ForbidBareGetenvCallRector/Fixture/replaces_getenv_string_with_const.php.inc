<?php

return new self(
    host: (string) getenv('DB_HOST'),
    port: (int) (getenv('DB_PORT') ?: 3306),
);

?>
-----
<?php

return new self(
    host: (string) getenv(\Utils\Rector\Tests\ForbidBareGetenvCallRector\Env::DB_HOST),
    port: (int) (getenv(\Utils\Rector\Tests\ForbidBareGetenvCallRector\Env::DB_PORT) ?: 3306),
);

?>
