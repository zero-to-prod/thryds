<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Framework\Attributes\CreateTable;
use ZeroToProd\Framework\Attributes\Migration;
use ZeroToProd\Thryds\Tables\User;

#[Migration(
    id: '0001',
    description: 'Create Users Table'
)]
#[CreateTable(User::class)]
final readonly class CreateUsersTable {}
