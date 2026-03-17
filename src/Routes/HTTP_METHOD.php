<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum HTTP_METHOD must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
