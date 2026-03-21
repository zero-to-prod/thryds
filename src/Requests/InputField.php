<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Requests;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Input;
use ZeroToProd\Thryds\Attributes\Validate;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Reflected input field metadata from a request class property.
 *
 * @see Input
 */
readonly class InputField
{
    public const string fields = 'fields';

    public function __construct(
        public string $name,
        public InputType $InputType,
        public string $label,
        public bool $required,
    ) {}

    /** Property name for the corresponding error on a view model. */
    public function errorKey(): string
    {
        return $this->name . '_error';
    }

    /** Reads the error message from a view model using the derived error key. */
    public function error(object $ViewModel): ?string
    {
        return $ViewModel->{$this->errorKey()};
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
     * Reflect all properties with {@see Input} attributes from a request class.
     *
     * @param class-string $class
     * @return list<self>
     */
    public static function reflect(string $class): array
    {
        $fields = [];

        foreach (new ReflectionClass(objectOrClass: $class)->getProperties() as $property) {
            $input_attributes = $property->getAttributes(Input::class);
            if ($input_attributes === []) {
                continue;
            }

            /** @var Input $Input */
            $Input = $input_attributes[0]->newInstance();

            $required = false;
            foreach ($property->getAttributes(Validate::class) as $validate_attribute) {
                /** @var Validate $Validate */
                $Validate = $validate_attribute->newInstance();
                foreach ($Validate->rules as [$rule]) {
                    if ($rule !== Rule::required) {
                        continue;
                    }
                    $required = true;
                    break 2;
                }
            }

            $fields[] = new self(
                name: $property->getName(),
                InputType: $Input->InputType,
                label: $Input->label,
                required: $required,
            );
        }

        return $fields;
    }
}
