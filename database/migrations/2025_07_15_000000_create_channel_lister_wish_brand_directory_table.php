<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('channel_lister_wish_brand_directory', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('brand_id')->comment('Brand ID');
            $table->string('brand_name')->comment('Brand Name');
            $table->string('brand_website_url')->nullable()->comment('Brand Website URL');
            $table->timestamp('last_update')->useCurrent()->useCurrentOnUpdate()->comment('On Update Current_Timestamp');

            $driver = Schema::getConnection()->getDriverName();
            $version = Schema::getConnection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            if ($driver === 'mysql' && version_compare($version, '5.6.0', '>=')) {
                // used instead of the DB::statement commented out below
                $table->fullText('brand_name');

                // this was outside of the create schema
                // Add FULLTEXT index using raw SQL since Laravel doesn't have native support for FULLTEXT
                // DB::statement('ALTER TABLE wish_brand_directory ADD FULLTEXT KEY `brand_name` (`brand_name`)');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_lister_wish_brand_directory');
    }
};
