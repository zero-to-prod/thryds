<?php

use Exception;

class Idempotent
{
    // TODO: [DetectStaleCodeReferencesRector] Comment references 'Exception::nonExistentMethod' which does not exist. Verify or remove.
    /**
     * @see Exception::nonExistentMethod()
     */
    public function handle(): void {}
}
