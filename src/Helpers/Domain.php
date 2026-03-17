<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

#[ClosedSet(Domain: Domain::closed_set_domains)]
/**
 * Closed set of domain names used by #[ClosedSet].
 *
 * Add a case here when introducing a new backed enum that represents a bounded domain.
 */
enum Domain: string
{
    case closed_set_domains = 'closed set domains';
    case application_environment = 'application environment';
    case blade_templates = 'Blade templates';
    case http_methods = 'HTTP methods';
    case log_severity_levels = 'log severity levels';
    case url_routes = 'URL routes';
}
