<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;

#[ClosedSet(Domain::closed_set_domains, addCase: 'Add enum case. Then use it in a #[ClosedSet] attribute on a new backed enum.')]
/**
 * Closed set of domain names used by #[ClosedSet].
 *
 * Add a case here when introducing a new backed enum that represents a bounded domain.
 */
enum Domain: string
{
    case closed_set_domains = 'closed_set_domains';
    case source_of_truth_concepts = 'source_of_truth_concepts';
    case application_environment = 'application_environment';
    case blade_directives = 'blade_directives';
    case blade_components = 'blade_components';
    case blade_templates = 'blade_templates';
    case http_methods = 'http_methods';
    case key_sources = 'key_sources';
    case log_severity_levels = 'log_severity_levels';
    case dev_path_groups = 'dev_path_groups';
    case dev_paths = 'dev_paths';
    case url_routes = 'url_routes';
    case error_messages = 'error_messages';
    case button_variants = 'button_variants';
    case button_sizes = 'button_sizes';
    case alert_variants = 'alert_variants';
    case input_types = 'input_types';
}
