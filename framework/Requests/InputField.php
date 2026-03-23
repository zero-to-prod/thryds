<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Requests;

use ReflectionClass;
use ZeroToProd\Framework\Attributes\Field;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\UI\InputType;
use ZeroToProd\Framework\Validation\FieldRules;
use ZeroToProd\Framework\Validation\Rule;
use ZeroToProd\Framework\Validation\Validator;

/**
 * Reflected input field metadata from a request class property.
 *
 * @see Field
 */
#[Infrastructure]
readonly class InputField
{
    public const string fields = 'fields';

    public function __construct(
        public string $name,
        public InputType $InputType,
        public string $label,
        public bool $required,
        public int $order = 0,
    ) {}

    /** Property name for the corresponding error on a view model. */
    public function errorKey(): string
    {
        return Validator::errorKey($this->name);
    }

    /** Reads the error message from a view model using the derived error key. */
    public function error(object $ViewModel): ?string
    {
        return $ViewModel->errors[$this->errorKey()] ?? null;
    }

    /** Reads the repopulated value from a view model. Password fields return null. */
    public function value(object $ViewModel): ?string
    {
        if ($this->InputType === InputType::password) {
            return null;
        }

        return $ViewModel->{$this->name};
    }

    /**
     * Reflect all properties with {@see Field} attributes from a request class.
     *
     * @param class-string $class
     * @return list<self>
     */
    public static function reflect(string $class): array
    {
        $fields = [];
        $ReflectionClass = new ReflectionClass(objectOrClass: $class);

        foreach ($ReflectionClass->getProperties() as $property) {
            $field_attrs = $property->getAttributes(Field::class);
            if ($field_attrs === []) {
                continue;
            }

            /** @var Field $Field */
            $Field = $field_attrs[0]->newInstance();
            $resolved_rules = FieldRules::resolve($Field);

            $required = false;
            foreach ($resolved_rules as [$rule]) {
                if ($rule === Rule::required) {
                    $required = true;
                    break;
                }
            }

            $fields[] = new self(
                $property->getName(),
                InputType: $Field->InputType,
                label: $Field->label,
                required: $required,
                order: $Field->order,
            );
        }

        usort(array: $fields, callback: static fn(self $a, self $b): int => $a->order <=> $b->order);

        return $fields;
    }
}
