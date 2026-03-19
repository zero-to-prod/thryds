<?php

use Exception;

/**
 * @link Exception::nonExistentMethod()
 */
class Bar {}
?>
-----
<?php

use Exception;

// TODO: [DetectStaleCodeReferencesRector] Comment references 'Exception::nonExistentMethod' which does not exist. Verify or remove.
/**
 * @link Exception::nonExistentMethod()
 */
class Bar {}
?>
