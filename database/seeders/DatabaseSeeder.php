<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@entrypoint.lk'], [
            'name' => 'EntryPoint Admin',
            'phone' => '0770000000',
            'password' => 'password',
            'role_id' => Role::SuperAdministrator,
        ]);

        $this->call([
            SettingSeeder::class,
            ActivityTypeSeeder::class,
            VenueSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
