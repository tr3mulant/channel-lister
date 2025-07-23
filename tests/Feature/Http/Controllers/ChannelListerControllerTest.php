<?php

declare(strict_types=1);

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;

test('index returns 200 status code', function (): void {
    // Create some test data manually
    ChannelListerField::create([
        'ordering' => 1,
        'marketplace' => 'amazon',
        'field_name' => 'title',
        'input_type' => InputType::TEXT,
        'type' => Type::CUSTOM,
        'required' => true,
        'grouping' => 'general',
    ]);

    $this->get(route('channel-lister'))
        ->assertStatus(200);
});
