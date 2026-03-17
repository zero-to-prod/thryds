<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Helpers\Component;

final class ComponentPushDirectivesTest extends TestCase
{
    #[Test]
    public function componentTemplatesDoNotUseBareAtPush(): void
    {
        $template_dir = dirname(__DIR__, 2) . '/templates/components';

        foreach (Component::cases() as $component) {
            $path = "{$template_dir}/{$component->value}.blade.php";
            $this->assertFileExists(filename: $path, message: "Component::{$component->name} has no matching template");

            $lines = file(filename: $path, flags: FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line_number => $line) {
                $this->assertDoesNotMatchRegularExpression(
                    '/@push\(/',
                    string: $line,
                    message: "Component::{$component->name} uses bare @push on line " . ($line_number + 1) . ". Use @pushOnce('stack', '{$component->value}') instead.",
                );
                $this->assertDoesNotMatchRegularExpression(
                    '/@prepend\(/',
                    string: $line,
                    message: "Component::{$component->name} uses bare @prepend on line " . ($line_number + 1) . ". Use @prependOnce('stack', '{$component->value}') instead.",
                );
            }
        }
    }
}
