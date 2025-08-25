<?php

namespace Workbench\Database\Seeders;

use IGE\ChannelLister\Data\DefaultFieldDefinitions;
use Illuminate\Database\Seeder;

class ChannelListerFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use configured database connection for Channel Lister operations
        DefaultFieldDefinitions::getConnection()
            ->table('channel_lister_fields')
            ->insert(DefaultFieldDefinitions::getFields());
    }
}
