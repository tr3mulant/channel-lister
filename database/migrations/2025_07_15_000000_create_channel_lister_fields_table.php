<?php

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
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
        Schema::create('channel_lister_fields', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->integer('ordering');
            $table->string('field_name')->unique()->comment('name of the field in channel advisor');
            $table->string('display_name')->nullable()->comment('name to display on form if different');
            $table->string('tooltip')->nullable();
            $table->string('example')->nullable();
            $table->string('marketplace')->comment('the platform this field applies to');

            // using array_map and the InputType enum from InputType.php
            $table->enum('input_type', array_map(fn (InputType $type) => $type->value, InputType::cases()));

            $table->text('input_type_aux')->nullable()->comment('separated by ||');
            $table->boolean('required')->comment('If marketplace is common and required is true will always be required. Otherwise only required if being sent to specified platform.');
            $table->string('grouping')->comment('section of page to put element on');
            $table->enum('type', array_map(fn (Type $type) => $type->value, Type::cases()))->comment('default channeladvisor field or custom attribute');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_lister_fields');
    }
};
