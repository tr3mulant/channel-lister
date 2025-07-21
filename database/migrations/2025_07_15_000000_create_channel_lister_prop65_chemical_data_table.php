<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store for Prop 65 Chemical Names and corresponding data
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('channel_lister_prop65_chemical_data', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('chemical')->comment('Chemical Name');
            $table->string('type_of_toxicity')->nullable();
            $table->string('listing_mechanism')->nullable();
            $table->string('cas_no')->nullable();
            $table->string('nsrl_or_madl')->nullable()->comment('µg/day');
            $table->timestamp('date_listed');           
            $table->timestamp('last_update')->useCurrent()->useCurrentOnUpdate();

            // Add index on chemical column
            $table->index('chemical');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_lister_prop65_chemical_data');
    }
};
