<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum AppEnv must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
