<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;
use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
    public string $message;
}
