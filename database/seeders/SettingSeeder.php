<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/** Writes the default site settings so they show up (and can be edited) in the admin panel. */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Settings::defaults() as $key => $value) {
            if (Setting::where('key', $key)->doesntExist()) {
                Settings::set($key, $value);
            }
        }
        Settings::flush();
    }
}
