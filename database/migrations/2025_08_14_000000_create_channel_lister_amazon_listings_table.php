<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_lister_amazon_listings', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft'); // draft, validating, validated, submitted, error
            $table->string('product_type');
            $table->string('marketplace_id');
            $table->json('form_data'); // The submitted form data
            $table->json('requirements')->nullable(); // Amazon requirements used for validation
            $table->json('validation_errors')->nullable(); // Validation errors if any
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('file_format')->nullable(); // csv, json, xml
            $table->text('submission_response')->nullable(); // Response from submission
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_lister_amazon_listings');
    }
};
