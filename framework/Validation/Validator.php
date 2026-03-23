<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Validation;

use ReflectionClass;
use ReflectionException;
use ZeroToProd\Framework\Attributes\Field;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\Matches;
use ZeroToProd\Framework\Attributes\ValidateWith;

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

        foreach ($ReflectionClass->getProperties() as $property) {
            $name = $property->getName();
            $field_attrs = $property->getAttributes(Field::class);
            $validate_with_attributes = $property->getAttributes(ValidateWith::class);
            $matches_attributes = $property->getAttributes(Matches::class);

            if ($field_attrs === [] && $validate_with_attributes === [] && $matches_attributes === []) {
                continue;
            }

            $value = $property->getValue(object: $model);

            if ($field_attrs !== []) {
                $resolved_rules = FieldRules::resolve($field_attrs[0]->newInstance());

                foreach ($resolved_rules as [$rule, $config]) {
                    if ($rule->passes($value, $config)) {
                        continue;
                    }
                    $errors[self::errorKey(property: $name)] = $rule->message(field: $name, config: $config);
                    break;
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
