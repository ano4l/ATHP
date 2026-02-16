<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::EMPLOYEE => 'Employee',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'danger',
            self::EMPLOYEE => 'info',
        };
    }
}
