<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Validate;
use ZeroToProd\Thryds\Attributes\ValidateWith;

final readonly class Validator
{
    private const string _error = '_error';

    /** @return array<string, string> */
    public static function validate(object $model): array
    {
        $errors = [];
        $ReflectionClass = new ReflectionClass(objectOrClass: $model);

        foreach ($ReflectionClass->getProperties() as $property) {
            $validate_attributes = $property->getAttributes(Validate::class);
            $validate_with_attributes = $property->getAttributes(ValidateWith::class);

            if ($validate_attributes === [] && $validate_with_attributes === []) {
                continue;
            }

            $name = $property->getName();
            $value = $property->getValue(object: $model);

            foreach ($validate_attributes as $attribute) {
                /** @var Validate $Validate */
                $Validate = $attribute->newInstance();
                foreach ($Validate->rules as [$rule, $config]) {
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
