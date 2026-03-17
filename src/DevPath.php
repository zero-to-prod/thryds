<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\Domain;
use ZeroToProd\Thryds\Helpers\Group;

#[ClosedSet(Domain::dev_paths, addCase: '1. Add enum case with #[Group(DevPathGroup::…)] attribute. Both consumers use DevFilter::isDevPath() so no further changes needed.')]
/**
 * Closed set of path segments that identify dev-only files.
 *
 * Each case is tagged with a #[Group] attribute indicating its DevPathGroup.
 */
enum DevPath: string
{
    #[Group(DevPathGroup::vendor)]
    case phpunit = '/vendor/phpunit/';

    #[Group(DevPathGroup::vendor)]
    case phpstan = '/vendor/phpstan/';

    #[Group(DevPathGroup::vendor)]
    case rector = '/vendor/rector/';

    #[Group(DevPathGroup::vendor)]
    case friendsofphp = '/vendor/friendsofphp/';

    #[Group(DevPathGroup::vendor)]
    case myclabs = '/vendor/myclabs/';

    #[Group(DevPathGroup::vendor)]
    case sebastian = '/vendor/sebastian/';

    #[Group(DevPathGroup::vendor)]
    case theseer = '/vendor/theseer/';

    #[Group(DevPathGroup::vendor)]
    case nikic_php_parser = '/vendor/nikic/php-parser/';

    #[Group(DevPathGroup::excluded_dir)]
    case var_cache = '/var/cache/';

    #[Group(DevPathGroup::excluded_dir)]
    case tests = '/tests/';

    #[Group(DevPathGroup::excluded_dir)]
    case utils = '/utils/';
}
