<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Services\CatalogService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a complete demo store.
     */
    public function run(CatalogService $catalog): void
    {
        $this->call([
            UserSeeder::class,
            CatalogSeeder::class,
            ReviewSeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
            ContentSeeder::class,
        ]);

        $catalog->flush();
    }
}
