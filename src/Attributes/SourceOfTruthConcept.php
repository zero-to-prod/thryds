<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::source_of_truth_concepts,
    addCase: '1. Add enum case. No other changes needed — SourceOfTruth consumers discover it by type.'
)]
/**
 * Closed set of concept names used by #[SourceOfTruth].
 *
 * Add a case here when introducing a new class that is the canonical owner of a domain concept.
 */
enum SourceOfTruthConcept: string
{
    case blade_template_names = 'Blade template names';
    case environment_variable_keys = 'environment variable keys';
    case route_paths = 'route paths';
}
