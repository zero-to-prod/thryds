<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Helpers\View;

final class ViewEnumTemplateFilesTest extends TestCase
{
    #[Test]
    public function allViewCasesHaveATemplateFile(): void
    {
        $template_dir = dirname(__DIR__, 2) . '/templates';

        foreach (View::cases() as $view) {
            $this->assertFileExists(
                "{$template_dir}/{$view->value}.blade.php",
                "View::{$view->name} has no matching template at templates/{$view->value}.blade.php",
            );
        }
    }
}
