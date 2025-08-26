<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user if one doesn't exist
        try {
            $user = \Workbench\App\Models\User::where('email', 'channel@lister.com')->first();

            if (! $user) {
                \Workbench\App\Models\User::create([
                    'name' => 'Channel Lister',
                    'email' => 'channel@lister.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // User creation failed, continue with other seeds
            $this->command->warn('Could not create test user: '.$e->getMessage());
        }

        $this->call([
            ChannelListerFieldSeeder::class,
        ]);
    }
}
