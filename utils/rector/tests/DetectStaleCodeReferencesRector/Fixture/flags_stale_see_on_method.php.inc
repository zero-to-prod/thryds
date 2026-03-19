<?php

use Exception;

class Foo
{
    /**
     * @see Exception::nonExistentMethod()
     */
    public function handle(): void {}
}
?>
-----
<?php

use Exception;

class Foo
{
    // TODO: [DetectStaleCodeReferencesRector] Comment references 'Exception::nonExistentMethod' which does not exist. Verify or remove.
    /**
     * @see Exception::nonExistentMethod()
     */
    public function handle(): void {}
}
?>
