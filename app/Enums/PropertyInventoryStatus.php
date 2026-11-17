<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyInventoryStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Withdrawn = 'withdrawn';
}
