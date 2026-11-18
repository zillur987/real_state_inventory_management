<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertySearchSort: string
{
    case Newest = 'newest';
    case PriceLow = 'price_low';
    case PriceHigh = 'price_high';
    case SizeLarge = 'size_large';
}
