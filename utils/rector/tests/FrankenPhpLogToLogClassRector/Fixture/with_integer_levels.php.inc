<?php

frankenphp_log('Hello World!', 4, ['key' => 'value']);
frankenphp_log('debug', -4);
frankenphp_log('info', 0);
frankenphp_log('error', 8, ['error' => 'details']);
frankenphp_log('default level');

?>
-----
<?php

\Fixture\Log::warn('Hello World!', ['key' => 'value']);
\Fixture\Log::debug('debug');
\Fixture\Log::info('info');
\Fixture\Log::error('error', ['error' => 'details']);
\Fixture\Log::info('default level');

?>
