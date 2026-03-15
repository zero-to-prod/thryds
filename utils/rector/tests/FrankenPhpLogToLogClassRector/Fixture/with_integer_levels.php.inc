<?php

frankenphp_log('Hello World!', 4, ['key' => 'value']);
frankenphp_log('debug', -4);
frankenphp_log('info', 0);
frankenphp_log('error', 8, ['error' => 'details']);
frankenphp_log('default level');

?>
-----
<?php

\ZeroToProd\Thryds\Log::warn('Hello World!', ['key' => 'value']);
\ZeroToProd\Thryds\Log::debug('debug');
\ZeroToProd\Thryds\Log::info('info');
\ZeroToProd\Thryds\Log::error('error', ['error' => 'details']);
\ZeroToProd\Thryds\Log::info('default level');

?>
