<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Finance = 'finance';

    public function isEmployee(): bool
    {
        return $this === self::Employee;
    }

    public function isFinance(): bool
    {
        return $this === self::Finance;
    }
}
