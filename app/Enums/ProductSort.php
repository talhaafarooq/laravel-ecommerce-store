<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Enums\Concerns\EnumOptions;

enum ProductSort: string implements HasLabel
{
    use EnumOptions;

    case Newest         = 'newest';
    case Popular        = 'popular';
    case TopRated       = 'rating';
    case PriceLowToHigh = 'price_asc';
    case PriceHighToLow = 'price_desc';
    case NameAsc        = 'name_asc';

    public function label(): string
    {
        return match ($this) {
            self::Newest         => 'Newest arrivals',
            self::Popular        => 'Best selling',
            self::TopRated       => 'Top rated',
            self::PriceLowToHigh => 'Price: low to high',
            self::PriceHighToLow => 'Price: high to low',
            self::NameAsc        => 'Name: A to Z',
        };
    }
}
