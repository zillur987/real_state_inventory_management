<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case SalesAgent = 'sales_agent';
    case LegalOfficer = 'legal_officer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::SalesAgent => 'Sales Agent',
            self::LegalOfficer => 'Legal Officer',
        };
    }
}
