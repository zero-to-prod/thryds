<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use Closure;
use LogicException;
use ReflectionAttribute;
use ReflectionEnumUnitCase;
use Stringable;
use ZeroToProd\Framework\Routes\Actions\Form;
use ZeroToProd\Framework\Routes\Actions\StaticView;
use ZeroToProd\Framework\Routes\Actions\Validated;
use ZeroToProd\Framework\Routes\HttpMethod;

/**
 * Declares one HTTP operation on a Route enum case.
 * Apply multiple times to register more than one method on the same path.
 *
 * The $action parameter accepts strategy objects or callable references:
 * - a static view strategy object                       — render a view
 * - a form strategy object with a view and controller   — form with validation
 * - a validated strategy object with controller/request  — validate then delegate
 * - a class-string for an invokable controller          — invokable dispatch
 * - an array callable (class-string + method name)      — array callable
 * - a first-class callable                              — first-class callable
 *
 * @param StaticView|Form|Validated|class-string|array{class-string, string}|Closure $action
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class Route
{
    use AttributeCache;

    public const string ACTION_NAME_CLOSURE = 'Closure';

    /** @param StaticView|Form|Validated|class-string|array{class-string, string}|Closure $action */
    public function __construct(
        public HttpMethod|string $HttpMethod,
        public StaticView|Form|Validated|string|array|Closure $action,
        public string|BackedEnum|null $description,
    ) {}

    /** Resolve the HTTP method, converting a string to HttpMethod if needed. */
    public function method(): HttpMethod
    {
        return $this->HttpMethod instanceof HttpMethod
            ? $this->HttpMethod
            : HttpMethod::from($this->HttpMethod);
    }

    /** Resolve description to a string, extracting the value from a BackedEnum if needed. */
    public function description(): ?string
    {
        return match (true) {
            $this->description === null            => null,
            $this->description instanceof BackedEnum => (string) $this->description->value,
            default                                 => $this->description,
        };
    }

    /** Short name describing the action type for manifests and diagnostics. */
    public function actionName(): string
    {
        return match (true) {
            $this->action instanceof Stringable => (string) $this->action,
            is_string($this->action)             => basename(str_replace('\\', '/', $this->action)),
            is_array($this->action)              => basename(str_replace('\\', '/', $this->action[0])) . '::' . $this->action[1],
            $this->action instanceof Closure     => self::ACTION_NAME_CLOSURE,
        };
    }

    /** @return self[] All HTTP operations declared on a route case. */
    public static function on(BackedEnum $BackedEnum): array
    {
        return self::cached('on', $BackedEnum::class . '::' . $BackedEnum->name, static fn(): array => array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): self => $ReflectionAttribute->newInstance(),
            new ReflectionEnumUnitCase($BackedEnum::class, $BackedEnum->name)
                ->getAttributes(self::class),
        ));
    }

    /** Returns the route-level description from the first operation with a non-null description. */
    public static function descriptionOf(BackedEnum $BackedEnum): string
    {
        return self::cached('descriptionOf', $BackedEnum::class . '::' . $BackedEnum->name, static function () use ($BackedEnum): string {
            foreach (self::on($BackedEnum) as $op) {
                $description = $op->description();
                if ($description !== null) {
                    return $description;
                }
            }
            throw new LogicException($BackedEnum::class . '::' . $BackedEnum->name . ' has no operation with a description.');
        });
    }

    /** Returns the View from the first action that carries one, or null. */
    public static function viewOf(BackedEnum $BackedEnum): ?BackedEnum
    {
        return self::cachedNullable('viewOf', $BackedEnum::class . '::' . $BackedEnum->name, static function () use ($BackedEnum): ?BackedEnum {
            foreach (self::on($BackedEnum) as $op) {
                if ($op->action instanceof StaticView || $op->action instanceof Form) {
                    return $op->action->BackedEnum;
                }
            }

            return null;
        });
    }
}
