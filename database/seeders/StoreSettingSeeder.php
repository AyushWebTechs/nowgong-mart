<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use App\Services\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StoreSettings::DEFAULTS as $key => $value) {
            StoreSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
