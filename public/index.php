<?php

declare(strict_types=1);

use ZeroToProd\Thryds\Log;

phpinfo();

Log::error('error', ['error' => 'details']);
