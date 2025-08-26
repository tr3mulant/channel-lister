<?php

namespace IGE\ChannelLister\Console;

use IGE\ChannelLister\Data\DefaultFieldDefinitions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedFieldsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel-lister:seed-fields {--force : Force seeding even if fields already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with default Channel Lister field definitions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        if (! $this->option('force') && $this->fieldsExist()) {
            $this->info('Channel Lister fields already exist. Use --force to reseed.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('channel_lister_fields')) {
            $this->error('Database table channel_lister_fields does not exist.');

            return self::FAILURE;
        }

        $connection = DefaultFieldDefinitions::getConnection();

        if ($this->option('force') && $this->fieldsExist()) {
            $this->warn('Force flag detected. Clearing existing fields...');

            $connection->table('channel_lister_fields')->truncate();
        }

        $this->info('Seeding Channel Lister fields...');

        $connection->table('channel_lister_fields')->insert($this->getFieldData());

        $count = $connection->table('channel_lister_fields')->count();

        $this->info("Successfully seeded {$count} Channel Lister fields.");

        return self::SUCCESS;
    }

    /**
     * Check if Channel Lister fields already exist in the database.
     */
    protected function fieldsExist(): bool
    {
        $connection = DefaultFieldDefinitions::getConnection();

        if (! Schema::hasTable('channel_lister_fields')) {
            return false;
        }

        return $connection->table('channel_lister_fields')->exists();
    }

    /**
     * Get the default field data to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getFieldData(): array
    {
        return DefaultFieldDefinitions::getFields();
    }
}
