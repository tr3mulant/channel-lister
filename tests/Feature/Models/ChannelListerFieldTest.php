<?php

declare(strict_types=1);

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ChannelListerField Model', function (): void {
    it('can be instantiated', function (): void {
        $field = new ChannelListerField;

        expect($field)->toBeInstanceOf(ChannelListerField::class);
    });

    it('uses HasFactory trait', function (): void {
        $traits = class_uses(ChannelListerField::class);

        expect($traits)->toContain(HasFactory::class);
    });

    it('has correct table name', function (): void {
        $field = new ChannelListerField;

        expect($field->getTable())->toBe('channel_lister_fields');
    });

    it('has correct fillable attributes', function (): void {
        $field = new ChannelListerField;
        $expectedFillable = [
            'ordering',
            'field_name',
            'display_name',
            'tooltip',
            'example',
            'marketplace',
            'input_type',
            'input_type_aux',
            'required',
            'grouping',
            'type',
        ];

        expect($field->getFillable())->toBe($expectedFillable);
    });

    it('has correct casts', function (): void {
        $field = new ChannelListerField;
        $expectedCasts = [
            'id' => 'int',
            'required' => 'boolean',
            'ordering' => 'integer',
            'input_type' => InputType::class,
            'type' => Type::class,
        ];

        expect($field->getCasts())->toMatchArray($expectedCasts);
    });

    it('can be created with factory', function (): void {
        $field = ChannelListerField::factory()->create();

        expect($field)->toBeInstanceOf(ChannelListerField::class)
            ->and($field->exists)->toBeTrue()
            ->and($field->id)->toBeInt();
    });

    it('can be created with specific attributes', function (): void {
        $attributes = [
            'ordering' => 1,
            'field_name' => 'test_field',
            'display_name' => 'Test Field',
            'tooltip' => 'This is a test field',
            'example' => 'example value',
            'marketplace' => 'amazon',
            'input_type' => InputType::TEXT,
            'input_type_aux' => 'option1||option2',
            'required' => true,
            'grouping' => 'basic',
            'type' => Type::CUSTOM,
        ];

        $field = ChannelListerField::factory()->create($attributes);

        expect($field->ordering)->toBe(1)
            ->and($field->field_name)->toBe('test_field')
            ->and($field->display_name)->toBe('Test Field')
            ->and($field->tooltip)->toBe('This is a test field')
            ->and($field->example)->toBe('example value')
            ->and($field->marketplace)->toBe('amazon')
            ->and($field->input_type)->toBe(InputType::TEXT)
            ->and($field->input_type_aux)->toBe('option1||option2')
            ->and($field->required)->toBeTrue()
            ->and($field->grouping)->toBe('basic')
            ->and($field->type)->toBe(Type::CUSTOM);
    });
});

describe('ChannelListerField Scopes', function (): void {
    beforeEach(function (): void {
        // Create test data
        ChannelListerField::factory()->create([
            'marketplace' => 'amazon',
            'required' => true,
            'grouping' => 'basic',
            'ordering' => 1,
        ]);

        ChannelListerField::factory()->create([
            'marketplace' => 'ebay',
            'required' => false,
            'grouping' => 'advanced',
            'ordering' => 2,
        ]);

        ChannelListerField::factory()->create([
            'marketplace' => 'amazon',
            'required' => true,
            'grouping' => 'basic',
            'ordering' => 3,
        ]);
    });

    it('can scope by marketplace', function (): void {
        $amazonFields = ChannelListerField::forMarketplace('amazon')->get();
        $ebayFields = ChannelListerField::forMarketplace('ebay')->get();

        expect($amazonFields)->toHaveCount(2)
            ->and($ebayFields)->toHaveCount(1);

        $amazonFields->each(function (ChannelListerField $field): void {
            expect($field->marketplace)->toBe('amazon');
        });

        $ebayFields->each(function (ChannelListerField $field): void {
            expect($field->marketplace)->toBe('ebay');
        });
    });

    it('can scope required fields', function (): void {
        $requiredFields = ChannelListerField::required()->get();

        expect($requiredFields)->toHaveCount(2);

        $requiredFields->each(function (ChannelListerField $field): void {
            expect($field->required)->toBeTrue();
        });
    });

    it('can scope by grouping', function (): void {
        $basicFields = ChannelListerField::byGrouping('basic')->get();
        $advancedFields = ChannelListerField::byGrouping('advanced')->get();

        expect($basicFields)->toHaveCount(2)
            ->and($advancedFields)->toHaveCount(1);

        $basicFields->each(function (ChannelListerField $field): void {
            expect($field->grouping)->toBe('basic');
        });

        $advancedFields->each(function (ChannelListerField $field): void {
            expect($field->grouping)->toBe('advanced');
        });
    });

    it('can scope ordered fields ascending', function (): void {
        $orderedFields = ChannelListerField::ordered()->get();

        expect($orderedFields)->toHaveCount(3)
            ->and($orderedFields->first()->ordering)->toBe(1)
            ->and($orderedFields->get(1)->ordering)->toBe(2)
            ->and($orderedFields->last()->ordering)->toBe(3);
    });

    it('can scope ordered fields descending', function (): void {
        $orderedFields = ChannelListerField::ordered('desc')->get();

        expect($orderedFields)->toHaveCount(3)
            ->and($orderedFields->first()->ordering)->toBe(3)
            ->and($orderedFields->get(1)->ordering)->toBe(2)
            ->and($orderedFields->last()->ordering)->toBe(1);
    });

    it('can chain scopes', function (): void {
        $chainedFields = ChannelListerField::forMarketplace('amazon')
            ->required()
            ->byGrouping('basic')
            ->ordered()
            ->get();

        expect($chainedFields)->toHaveCount(2);

        $chainedFields->each(function (ChannelListerField $field): void {
            expect($field->marketplace)->toBe('amazon')
                ->and($field->required)->toBeTrue()
                ->and($field->grouping)->toBe('basic');
        });

        // Check ordering
        expect($chainedFields->first()->ordering)->toBe(1)
            ->and($chainedFields->last()->ordering)->toBe(3);
    });
});

describe('ChannelListerField Methods', function (): void {
    it('can check if field is custom', function (): void {
        $customField = ChannelListerField::factory()->create([
            'type' => Type::CUSTOM,
        ]);

        $channelAdvisorField = ChannelListerField::factory()->create([
            'type' => Type::CHANNEL_ADVISOR,
        ]);

        expect($customField->isCustom())->toBeTrue()
            ->and($channelAdvisorField->isCustom())->toBeFalse();
    });

    it('can check if field is channel advisor', function (): void {
        $customField = ChannelListerField::factory()->create([
            'type' => Type::CUSTOM,
        ]);

        $channelAdvisorField = ChannelListerField::factory()->create([
            'type' => Type::CHANNEL_ADVISOR,
        ]);

        expect($channelAdvisorField->isChannelAdvisor())->toBeTrue()
            ->and($customField->isChannelAdvisor())->toBeFalse();
    });
});

describe('ChannelListerField Attributes', function (): void {
    it('parses input_type_aux correctly when getting', function (): void {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'option1||option2||option3',
        ]);

        expect($field->input_type_aux)->toBe('option1||option2||option3');

        expect($field->getInputTypeAuxOptions())->toBe(['option1', 'option2', 'option3']);
    });

    it('handles empty input_type_aux when getting', function (): void {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => '',
        ]);

        expect($field->input_type_aux)->toBe('');

        expect($field->getInputTypeAuxOptions())->toBe('');
    });

    it('handles zero string input_type_aux when getting', function (): void {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => '0',
        ]);

        expect($field->input_type_aux)->toBe('0');

        expect($field->getInputTypeAuxOptions())->toBe('0');
    });

    it('converts array to string when setting input_type_aux', function (): void {
        $field = new ChannelListerField;
        $field->input_type_aux = ['option1', 'option2', 'option3'];

        expect($field->input_type_aux)->toBe('option1||option2||option3');

        expect($field->getInputTypeAuxOptions())->toBe(['option1', 'option2', 'option3']);
    });

    it('returns display_name when set', function (): void {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field',
            'display_name' => 'Custom Display Name',
        ]);

        expect($field->display_name)->toBe('Custom Display Name');
    });

    it('generates display_name from field_name when not set', function (): void {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field_name',
            'display_name' => null,
        ]);

        expect($field->display_name)->toBe('Test Field Name');
    });

    it('generates display_name from field_name when empty string', function (): void {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'another_test_field',
            'display_name' => '',
        ]);

        expect($field->display_name)->toBe('Another Test Field');
    });

    it('generates display_name from field_name when zero string', function (): void {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'zero_field',
            'display_name' => '0',
        ]);

        expect($field->display_name)->toBe('Zero Field');
    });
});

describe('ChannelListerField Enum Casting', function (): void {
    it('casts input_type to InputType enum', function (): void {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::TEXT,
        ]);

        expect($field->input_type)->toBeInstanceOf(InputType::class)
            ->and($field->input_type)->toBe(InputType::TEXT);
    });

    it('casts type to Type enum', function (): void {
        $field = ChannelListerField::factory()->create([
            'type' => Type::CUSTOM,
        ]);

        expect($field->type)->toBeInstanceOf(Type::class)
            ->and($field->type)->toBe(Type::CUSTOM);
    });

    it('handles all InputType enum values', function (): void {
        foreach (InputType::cases() as $inputType) {
            $field = ChannelListerField::factory()->create([
                'input_type' => $inputType,
            ]);

            expect($field->input_type)->toBe($inputType);
        }
    });

    it('handles all Type enum values', function (): void {
        foreach (Type::cases() as $type) {
            $field = ChannelListerField::factory()->create([
                'type' => $type,
            ]);

            expect($field->type)->toBe($type);
        }
    });
});

describe('ChannelListerField Validation', function (): void {
    it('requires field_name', function (): void {
        expect(function (): void {
            ChannelListerField::factory()->create([
                'field_name' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires marketplace', function (): void {
        expect(function (): void {
            ChannelListerField::factory()->create([
                'marketplace' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires input_type', function (): void {
        expect(function (): void {
            ChannelListerField::factory()->create([
                'input_type' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires grouping', function (): void {
        expect(function (): void {
            ChannelListerField::factory()->create([
                'grouping' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires type', function (): void {
        expect(function (): void {
            ChannelListerField::factory()->create([
                'type' => null,
            ]);
        })->toThrow(Exception::class);
    });
});
