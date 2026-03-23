<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\EdgeKind;
use ZeroToProd\Thryds\Attributes\Group;

#[ClosedSet(
    Domain::closed_set_domains,
    addCase: 'Add enum case with #[Group(EdgeKind::…)]. Then use it in a #[ClosedSet] attribute on a new backed enum.'
)]
/**
 * Closed set of domain names used by #[ClosedSet].
 *
 * Each case is grouped by EdgeKind so the attribute graph can infer
 * the semantic kind of edges targeting enums in that domain.
 */
enum Domain: string
{
    #[Group(EdgeKind::type_system)]
    case closed_set_domains = 'closed_set_domains';

    #[Group(EdgeKind::type_system)]
    case source_of_truth_concepts = 'source_of_truth_concepts';

    #[Group(EdgeKind::type_system)]
    case application_environment = 'application_environment';

    #[Group(EdgeKind::composition)]
    case blade_directives = 'blade_directives';

    #[Group(EdgeKind::composition)]
    case blade_components = 'blade_components';

    #[Group(EdgeKind::composition)]
    case blade_templates = 'blade_templates';

    #[Group(EdgeKind::navigation)]
    case handler_strategies = 'handler_strategies';

    #[Group(EdgeKind::navigation)]
    case http_methods = 'http_methods';

    #[Group(EdgeKind::type_system)]
    case key_sources = 'key_sources';

    #[Group(EdgeKind::type_system)]
    case log_severity_levels = 'log_severity_levels';

    #[Group(EdgeKind::type_system)]
    case dev_path_groups = 'dev_path_groups';

    #[Group(EdgeKind::type_system)]
    case dev_paths = 'dev_paths';

    #[Group(EdgeKind::navigation)]
    case url_routes = 'url_routes';

    #[Group(EdgeKind::navigation)]
    case route_guards = 'route_guards';

    #[Group(EdgeKind::navigation)]
    case route_sources = 'route_sources';

    #[Group(EdgeKind::type_system)]
    case error_messages = 'error_messages';

    #[Group(EdgeKind::composition)]
    case button_variants = 'button_variants';

    #[Group(EdgeKind::composition)]
    case button_sizes = 'button_sizes';

    #[Group(EdgeKind::composition)]
    case alert_variants = 'alert_variants';

    #[Group(EdgeKind::composition)]
    case input_types = 'input_types';

    #[Group(EdgeKind::composition)]
    case component_props = 'component_props';

    #[Group(EdgeKind::composition)]
    case layouts = 'layouts';

    #[Group(EdgeKind::schema)]
    case sql_data_types = 'sql_data_types';

    #[Group(EdgeKind::schema)]
    case sql_storage_engines = 'sql_storage_engines';

    #[Group(EdgeKind::schema)]
    case sql_charsets = 'sql_charsets';

    #[Group(EdgeKind::schema)]
    case sql_collations = 'sql_collations';

    #[Group(EdgeKind::schema)]
    case sql_referential_actions = 'sql_referential_actions';

    #[Group(EdgeKind::schema)]
    case database_table_columns = 'database_table_columns';

    #[Group(EdgeKind::type_system)]
    case migration_statuses = 'migration_statuses';

    #[Group(EdgeKind::data_flow)]
    case persistence_hooks = 'persistence_hooks';

    #[Group(EdgeKind::data_flow)]
    case validation_rules = 'validation_rules';

    #[Group(EdgeKind::schema)]
    case database_table_names = 'database_table_names';

    #[Group(EdgeKind::type_system)]
    case edge_kinds = 'edge_kinds';

    #[Group(EdgeKind::type_system)]
    case namespace_layers = 'namespace_layers';

    #[Group(EdgeKind::data_flow)]
    case config_keys = 'config_keys';

    #[Group(EdgeKind::schema)]
    case schema_sync_sources = 'schema_sync_sources';

    #[Group(EdgeKind::schema)]
    case sort_directions = 'sort_directions';

    #[Group(EdgeKind::schema)]
    case database_drivers = 'database_drivers';
}
