<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use Random\RandomException;
use ReflectionEnumBackedCase;
use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\ResolvesTo;
use ZeroToProd\Framework\Queries\Resolvers\NowResolver;
use ZeroToProd\Framework\Queries\Resolvers\PasswordHashResolver;
use ZeroToProd\Framework\Queries\Resolvers\RandomIdResolver;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::persistence_hooks,
    addCase: 'Add enum case with #[ResolvesTo] attribute pointing to a class carrying #[PersistResolver].'
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
        $resolver = $this->resolver();
        assert(method_exists(object_or_class: $resolver, method: 'resolve'));

        return $resolver->resolve($value);
    }

    private function resolver(): object
    {
        return new ReflectionEnumBackedCase(self::class, $this->name)
            ->getAttributes(ResolvesTo::class)[0]
            ->newInstance()
            ->newResolver();
    }
}
