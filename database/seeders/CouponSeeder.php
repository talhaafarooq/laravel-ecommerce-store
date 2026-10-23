<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\ValueObjects\Money;
use Illuminate\Database\Seeder;

final class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            ['code' => 'WELCOME10', 'description' => '10% off your first order', 'type' => CouponType::Percentage, 'value' => 10, 'usage_limit_per_user' => 1, 'max_discount' => Money::fromDecimal(50)],
            ['code' => 'SAVE20', 'description' => '$20 off orders over $150', 'type' => CouponType::Fixed, 'value' => 2000, 'min_subtotal' => Money::fromDecimal(150)],
            ['code' => 'FLASH25', 'description' => 'Flash sale: 25% off', 'type' => CouponType::Percentage, 'value' => 25, 'usage_limit' => 100, 'expires_at' => now()->addDays(14)],
            ['code' => 'SUMMER15', 'description' => 'Summer sale (expired)', 'type' => CouponType::Percentage, 'value' => 15, 'starts_at' => now()->subMonths(4), 'expires_at' => now()->subMonth()],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->create([...$coupon, 'is_active' => true]);
        }
    }
}
