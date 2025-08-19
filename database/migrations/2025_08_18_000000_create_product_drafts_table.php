<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_drafts', function (Blueprint $table) {
            $table->id();
            $table->json('form_data')->comment('All form data from all marketplace tabs');
            $table->string('status', 50)->default('draft')->comment('draft, validated, exported');
            $table->json('validation_errors')->nullable()->comment('Validation errors from any marketplace');
            $table->json('export_formats')->nullable()->comment('Requested export formats: rithum, amazon, ebay, etc.');
            $table->string('title')->nullable()->comment('Product title for easy identification');
            $table->string('sku')->nullable()->comment('Product SKU for easy identification');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_drafts');
    }
};
