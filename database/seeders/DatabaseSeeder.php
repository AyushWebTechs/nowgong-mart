<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([StoreSettingSeeder::class, CategorySeeder::class]);

        if (! app()->environment('local')) {
            $this->command?->info('Production: create the owner account with `php artisan mart:create-admin`.');

            return;
        }

        $this->call([DemoCatalogueSeeder::class, DemoImageSeeder::class]);

        $password = Str::password(12, symbols: false);
        $accounts = [
            ['Store Owner (dev)', '9000000001', UserRole::Admin],
            ['Delivery Ravi (dev)', '9000000002', UserRole::Delivery],
            ['Test Customer (dev)', '9000000003', UserRole::Customer],
        ];

        foreach ($accounts as [$name, $phone, $role]) {
            $user = User::firstOrNew(['phone' => $phone]);
            $user->fill(['name' => $name, 'password' => $password]);
            $user->forceFill(['role' => $role, 'is_active' => true])->save();
        }

        $this->command?->warn("Local dev accounts (phone / password): 9000000001 admin, 9000000002 delivery, 9000000003 customer — password: {$password}");
    }
}
