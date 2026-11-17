<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyCategory: string
{
    case Apartment = 'apartment';
    case Flat = 'flat';
    case Duplex = 'duplex';
    case Penthouse = 'penthouse';
    case Land = 'land';
    case Plot = 'plot';
    case Commercial = 'commercial';
}
