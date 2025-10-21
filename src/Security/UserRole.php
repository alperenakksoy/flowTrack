<?php

namespace App\Security;

class UserRole
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_MANAGER = 'ROLE_MANAGER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public static function getChoices(): array
    {
        return [
            'User' => self::ROLE_USER,
            'Manager' => self::ROLE_MANAGER,
            'Admin' => self::ROLE_ADMIN,
        ];
    }
}
