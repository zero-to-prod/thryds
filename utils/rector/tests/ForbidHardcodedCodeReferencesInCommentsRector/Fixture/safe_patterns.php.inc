<?php

use App\Routes\RouteRegistrar;

class SafeExample
{
    /**
     * @see RouteRegistrar::register
     * @param RouteRegistrar $registrar The registrar instance.
     * @return array<string, mixed>
     * @throws \RuntimeException
     * @phpstan-param class-string<RouteRegistrar> $class
     */
    public function withTags(RouteRegistrar $registrar): array { return []; }

    /** Delegates to the registrar via {@see RouteRegistrar::register}. */
    public function withInlineSee(): void {}

    /** Binds all declared routes to the HTTP kernel. */
    public function evergreen(): void {}
}
?>
