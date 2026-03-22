<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;
use ReflectionEnumBackedCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\ResolvesTo;
use ZeroToProd\Thryds\Queries\Resolvers\NowResolver;
use ZeroToProd\Thryds\Queries\Resolvers\PasswordHashResolver;
use ZeroToProd\Thryds\Queries\Resolvers\RandomIdResolver;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::persistence_hooks,
    addCase: 'Add enum case with #[ResolvesTo] attribute pointing to a PersistResolver implementation.'
)]
enum Persist: string
{
    #[ResolvesTo(RandomIdResolver::class)]
    case random_id     = 'random_id';

    #[ResolvesTo(PasswordHashResolver::class)]
    case password_hash = 'password_hash';

    #[ResolvesTo(NowResolver::class)]
    case now           = 'now';

    /** @throws RandomException */
    public function resolve(mixed $value): string
    {
        return $this->resolver()->resolve($value);
    }

    private function resolver(): PersistResolver
    {
        return new ReflectionEnumBackedCase(self::class, $this->name)
            ->getAttributes(ResolvesTo::class)[0]
            ->newInstance()
            ->newResolver();
    }
}
