<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Validates;
use ZeroToProd\Thryds\Attributes\ValidateWith;

#[Infrastructure]
final readonly class Validator
{
    private const string _error = '_error';

    /** @return array<string, string> */
    public static function validate(object $model): array
    {
        $errors = [];
        $ReflectionClass = new ReflectionClass(objectOrClass: $model);

        /** @var array<string, list<Validates>> */
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

            if ($class_validates === [] && $validate_with_attributes === []) {
                continue;
            }

            $value = $property->getValue(object: $model);

            foreach ($class_validates as $Validates) {
                foreach ($Validates->rules as [$rule, $config]) {
                    if ($rule->passes($value, $config, context: $model)) {
                        continue;
                    }
                    $errors[$name . self::_error] = $rule->message(field: $name, config: $config);
                    break 2;
                }
            }

            if (isset($errors[$name . self::_error])) {
                continue;
            }

            foreach ($validate_with_attributes as $attribute) {
                /** @var ValidationRule $ValidationRule */
                $ValidationRule = new ($attribute->newInstance()->rule)();
                if ($ValidationRule->passes($value, context: $model)) {
                    continue;
                }
                $errors[$name . self::_error] = $ValidationRule->message(field: $name);
                break;
            }
        }

        return $errors;
    }
}
