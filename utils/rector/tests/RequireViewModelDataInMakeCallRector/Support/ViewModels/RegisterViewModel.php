<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireViewModelDataInMakeCallRector\Support\ViewModels;

/** Registration form state. */
class RegisterViewModel
{
    public const string view_key = 'RegisterViewModel';
    public string $name = '';
    public string $email = '';
}
