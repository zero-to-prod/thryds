<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

#[ClosedSet(Domain::key_sources, addCase: 'Add enum case. Then use it in a #[KeyRegistry] attribute on a new constants class.')]
/**
 * Closed set of data source names used by #[KeyRegistry].
 *
 * Add a case here when introducing a new constants class that names keys from a new data source.
 */
enum Source: string
{
    case blade_directive_names = 'Blade directive names';
    case http_headers = 'HTTP headers';
    case log_context_array = 'Log context array';
    case opcache_get_status = 'opcache_get_status()';
    case server_env = '$_SERVER / $_ENV';
    case vite_entry_points = 'Vite entry points';
}
