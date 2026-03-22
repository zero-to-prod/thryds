<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ReflectionClass;
use ReflectionException;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Matches;
use ZeroToProd\Thryds\Attributes\Validates;
use ZeroToProd\Thryds\Attributes\ValidateWith;

#[Infrastructure]
final readonly class Validator
{
    private const string _error = '_error';

    public static function errorKey(string $property): string
    {
        return $property . self::_error;
    }

    /**
     * @return array<string, string>
     * @throws ReflectionException
     */
    public static function validate(object $model): array
    {
        $errors = [];
        $ReflectionClass = new ReflectionClass(objectOrClass: $model);

        /** @var array<string, list<Validates>> $class_validation_map */
        $class_validation_map = [];
        foreach ($ReflectionClass->getAttributes(Validates::class) as $attribute) {
            /** @var Validates $Validates */
            $Validates = $attribute->newInstance();
            $class_validation_map[$Validates->property][] = $Validates;
        }

        foreach ($ReflectionClass->getProperties() as $property) {
            $name = $property->getName();
            $class_validates = $class_validation_map[$name] ?? [];
            $validate_with_attributes = $property->getAttributes(ValidateWith::class);
            $matches_attributes = $property->getAttributes(Matches::class);

            if ($class_validates === [] && $validate_with_attributes === [] && $matches_attributes === []) {
                continue;
            }

            $value = $property->getValue(object: $model);

            foreach ($class_validates as $Validates) {
                foreach ($Validates->rules as [$rule, $config]) {
                    if ($rule->passes($value, $config)) {
                        continue;
                    }
                    $errors[self::errorKey(property: $name)] = $rule->message(field: $name, config: $config);
                    break 2;
                }
            }

            if (isset($errors[self::errorKey(property: $name)])) {
                continue;
            }

            foreach ($validate_with_attributes as $attribute) {
                $rule = new ($attribute->newInstance()->rule)();
                assert(method_exists(object_or_class: $rule, method: 'passes') && method_exists(object_or_class: $rule, method: 'message'));
                if ($rule->passes($value, $model)) {
                    continue;
                }
                $errors[self::errorKey(property: $name)] = $rule->message($name);
                break;
            }

            if (isset($errors[self::errorKey(property: $name)])) {
                continue;
            }

            foreach ($matches_attributes as $attribute) {
                /** @var Matches $Matches */
                $Matches = $attribute->newInstance();
                if ($value === $property->getDeclaringClass()->getProperty(name: $Matches->property)->getValue(object: $model)) {
                    continue;
                }
                $errors[self::errorKey(property: $name)] = $Matches->message();
                break;
            }
        }

        return $errors;
    }
}
