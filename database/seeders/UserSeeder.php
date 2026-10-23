<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts. Both use the password "password".
 */
final class UserSeeder extends Seeder
{
    public const string ADMIN_EMAIL = 'admin@shopnest.test';

    public const string CUSTOMER_EMAIL = 'customer@shopnest.test';

    public function run(): void
    {
        User::factory()->admin()->create([
            'name'  => 'Store Admin',
            'email' => self::ADMIN_EMAIL,
            'phone' => '+1 (555) 010-0001',
            'role'  => UserRole::Admin,
        ]);

        $customer = User::factory()->create([
            'name'  => 'Jordan Rivera',
            'email' => self::CUSTOMER_EMAIL,
            'phone' => '+1 (555) 010-0002',
        ]);

        Address::factory()->for($customer)->default()->create([
            'label'       => 'Home',
            'full_name'   => $customer->name,
            'phone'       => (string) $customer->phone,
            'line1'       => '742 Evergreen Terrace',
            'line2'       => null,
            'city'        => 'Springfield',
            'state'       => 'OR',
            'postal_code' => '97403',
            'country'     => 'US',
        ]);

        Address::factory()->for($customer)->create([
            'label'       => 'Office',
            'full_name'   => $customer->name,
            'phone'       => (string) $customer->phone,
            'line1'       => '1600 Market Street',
            'line2'       => 'Floor 12',
            'city'        => 'San Francisco',
            'state'       => 'CA',
            'postal_code' => '94103',
            'country'     => 'US',
        ]);

        User::factory(18)->create()->each(static function (User $user): void {
            Address::factory()->for($user)->default()->create(['full_name' => $user->name]);
        });
    }
}
