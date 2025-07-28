<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Channel Lister',
            'email' => 'channel@lister.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            ChannelListerFieldSeeder::class,
        ]);
    }
}
