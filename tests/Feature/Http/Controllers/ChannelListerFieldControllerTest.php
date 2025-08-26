<?php

declare(strict_types=1);

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
// uses(RefreshDatabase::class, InteractsWithViews::class);

describe('ChannelListerFieldController', function (): void {
    describe('index', function (): void {
        it('displays the index page with no fields', function (): void {
            $this->get(route('channel-lister-field.index'))
                ->assertStatus(200)
                ->assertViewIs('channel-lister::channel-lister-field.index')
                ->assertViewHas('fields')
                ->assertSee('No Channel Lister Fields Found');
        });

        it('displays the index page with fields', function (): void {
            ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            ChannelListerField::create([
                'ordering' => 2,
                'field_name' => 'price',
                'display_name' => 'Price',
                'marketplace' => 'ebay',
                'input_type' => InputType::CURRENCY,
                'required' => true,
                'grouping' => 'pricing',
                'type' => Type::CUSTOM,
            ]);

            $this->get(route('channel-lister-field.index'))
                ->assertStatus(200)
                ->assertViewIs('channel-lister::channel-lister-field.index')
                ->assertViewHas('fields')
                ->assertSee('Product Title')
                ->assertSee('Price')
                ->assertSee('amazon')
                ->assertSee('ebay')
                ->assertDontSee('No fields found');
        });

        it('paginates fields correctly', function (): void {
            // Create 25 fields to test pagination
            for ($i = 1; $i <= 25; $i++) {
                ChannelListerField::create([
                    'ordering' => $i,
                    'field_name' => "field_{$i}",
                    'marketplace' => 'amazon',
                    'input_type' => InputType::TEXT,
                    'required' => false,
                    'grouping' => 'test',
                    'type' => Type::CUSTOM,
                ]);
            }

            $response = $this->get(route('channel-lister-field.index'));

            $response->assertStatus(200)
                ->assertViewHas('fields');

            $fields = $response->viewData('fields');
            expect($fields->count())->toBe(15); // Default pagination
            expect($fields->hasPages())->toBeTrue();
        });
    });

    describe('show', function (): void {
        it('displays a specific field', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'tooltip' => 'Enter the product title',
                'example' => 'iPhone 15 Pro',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'input_type_aux' => 'max-length:255',
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $this->get(route('channel-lister-field.show', $field))
                ->assertStatus(200)
                ->assertViewIs('channel-lister::channel-lister-field.show')
                ->assertViewHas('field', $field)
                ->assertSee('Product Title')
                ->assertSee('product_title')
                ->assertSee('Enter the product title')
                ->assertSee('iPhone 15 Pro')
                ->assertSee('amazon')
                ->assertSee('Required')
                ->assertSee('basic');
        });

        it('returns 404 for non-existent field', function (): void {
            $this->get(route('channel-lister-field.show', ['field' => 999]))
                ->assertStatus(404);
        });
    });

    describe('create', function (): void {
        it('displays the create form', function (): void {
            $this->get(route('channel-lister-field.create'))
                ->assertStatus(200)
                ->assertViewIs('channel-lister::channel-lister-field.create')
                ->assertSee('Create New Channel Lister Field')
                ->assertSee('Field Name')
                ->assertSee('Display Name')
                ->assertSee('Marketplace')
                ->assertSee('Input Type')
                ->assertSee('Type');
        });
    });

    describe('store', function (): void {
        it('creates a new field with valid data', function (): void {
            $data = [
                'ordering' => 1,
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'tooltip' => 'Enter the product title',
                'example' => 'iPhone 15 Pro',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT->value,
                'input_type_aux' => 'max-length:255',
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertRedirect(route('channel-lister-field.index'))
                ->assertSessionHas('success', 'Channel Lister Field created successfully.');

            $this->assertDatabaseHas('channel_lister_fields', [
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'marketplace' => 'amazon',
                'required' => true,
            ]);
        });

        it('validates required fields', function (): void {
            $this->post(route('channel-lister-field.store'), [])
                ->assertSessionHasErrors([
                    'ordering',
                    'field_name',
                    'marketplace',
                    'input_type',
                    'required',
                    'grouping',
                    'type',
                ]);
        });

        it('validates field name uniqueness per marketplace', function (): void {
            ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => false,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $data = [
                'ordering' => 2,
                'field_name' => 'product_title', // Same name
                'marketplace' => 'amazon', // Same marketplace
                'input_type' => InputType::TEXT->value,
                'required' => false,
                'grouping' => 'basic',
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertSessionHasErrors(['field_name']);
        });

        it('does not allows same field name for different marketplaces', function (): void {
            ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => false,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $data = [
                'ordering' => 2,
                'field_name' => 'product_title', // Same name
                'marketplace' => 'ebay', // Different marketplace
                'input_type' => InputType::TEXT->value,
                'required' => false,
                'grouping' => 'basic',
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertSessionHasErrors(['field_name']);
        });

        it('validates enum values', function (): void {
            $data = [
                'ordering' => 1,
                'field_name' => 'test_field',
                'marketplace' => 'amazon',
                'input_type' => 'invalid_input_type',
                'required' => false,
                'grouping' => 'basic',
                'type' => 'invalid_type',
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertSessionHasErrors(['input_type', 'type']);
        });

        it('validates string length limits', function (): void {
            $data = [
                'ordering' => 1,
                'field_name' => str_repeat('a', 256), // Too long
                'display_name' => str_repeat('b', 256), // Too long
                'marketplace' => str_repeat('c', 101), // Too long
                'input_type' => InputType::TEXT->value,
                'required' => false,
                'grouping' => str_repeat('d', 101), // Too long
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertSessionHasErrors([
                    'field_name',
                    'display_name',
                    'marketplace',
                    'grouping',
                ]);
        });
    });

    describe('edit', function (): void {
        it('displays the edit form with existing data', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $this->get(route('channel-lister-field.edit', $field))
                ->assertStatus(200)
                ->assertViewIs('channel-lister::channel-lister-field.edit')
                ->assertViewHas('field', $field)
                ->assertSee('Edit Channel Lister Field')
                ->assertSee('product_title')
                ->assertSee('Product Title');
        });

        it('returns 404 for non-existent field', function (): void {
            $this->get(route('channel-lister-field.edit', ['field' => 999]))
                ->assertStatus(404);
        });
    });

    describe('update', function (): void {
        it('updates an existing field with valid data', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'display_name' => 'Product Title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $updateData = [
                'ordering' => 2,
                'field_name' => 'updated_title',
                'display_name' => 'Updated Product Title',
                'tooltip' => 'Updated tooltip',
                'marketplace' => 'ebay',
                'input_type' => InputType::TEXTAREA->value,
                'required' => false,
                'grouping' => 'advanced',
                'type' => Type::CHANNEL_ADVISOR->value,
            ];

            $this->put(route('channel-lister-field.update', $field), $updateData)
                ->assertRedirect(route('channel-lister-field.index'))
                ->assertSessionHas('success', 'Channel Lister Field updated successfully.');

            $field->refresh();

            expect($field->field_name)->toBe('updated_title');
            expect($field->display_name)->toBe('Updated Product Title');
            expect($field->marketplace)->toBe('ebay');
            expect($field->input_type)->toBe(InputType::TEXTAREA);
            expect($field->required)->toBeFalse();
            expect($field->type)->toBe(Type::CHANNEL_ADVISOR);
        });

        it('validates required fields on update', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $this->put(route('channel-lister-field.update', $field), [
                'field_name' => '', // Empty required field
                'marketplace' => '',
            ])
                ->assertSessionHasErrors(['field_name', 'marketplace']);
        });

        it('validates uniqueness on update excluding current record', function (): void {
            $field1 = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $field2 = ChannelListerField::create([
                'ordering' => 2,
                'field_name' => 'product_price',
                'marketplace' => 'amazon',
                'input_type' => InputType::CURRENCY,
                'required' => true,
                'grouping' => 'pricing',
                'type' => Type::CUSTOM,
            ]);

            // Try to update field2 to have the same name as field1
            $this->put(route('channel-lister-field.update', $field2), [
                'ordering' => 2,
                'field_name' => 'product_title', // Same as field1
                'marketplace' => 'amazon', // Same marketplace
                'input_type' => InputType::CURRENCY->value,
                'required' => true,
                'grouping' => 'pricing',
                'type' => Type::CUSTOM->value,
            ])
                ->assertSessionHasErrors(['field_name']);
        });

        it('allows updating field to keep same name', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $updateData = [
                'ordering' => 2,
                'field_name' => 'product_title', // Keep same name
                'display_name' => 'Updated Display Name',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXTAREA->value,
                'required' => false,
                'grouping' => 'advanced',
                'type' => Type::CUSTOM->value,
            ];

            $this->put(route('channel-lister-field.update', $field), $updateData)
                ->assertRedirect(route('channel-lister-field.index'))
                ->assertSessionHas('success');
        });

        it('returns 404 for non-existent field', function (): void {
            $this->put(route('channel-lister-field.update', ['field' => 999]), [])
                ->assertStatus(404);
        });
    });

    describe('destroy', function (): void {
        it('deletes an existing field', function (): void {
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'product_title',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT,
                'required' => true,
                'grouping' => 'basic',
                'type' => Type::CUSTOM,
            ]);

            $this->delete(route('channel-lister-field.destroy', $field))
                ->assertRedirect(route('channel-lister-field.index'))
                ->assertSessionHas('success', 'Channel Lister Field deleted successfully.');

            $this->assertDatabaseMissing('channel_lister_fields', [
                'id' => $field->id,
            ]);
        });

        it('returns 404 for non-existent field', function (): void {
            $this->delete(route('channel-lister-field.destroy', ['field' => 999]))
                ->assertStatus(404);
        });

        // it('handles soft deletes if implemented', function (): void {
        //     $field = ChannelListerField::create([
        //         'ordering' => 1,
        //         'field_name' => 'product_title',
        //         'marketplace' => 'amazon',
        //         'input_type' => InputType::TEXT,
        //         'required' => true,
        //         'grouping' => 'basic',
        //         'type' => Type::CUSTOM,
        //     ]);

        //     $fieldId = $field->id;

        //     $this->delete(route('channel-lister-field.destroy', $field))
        //         ->assertRedirect(route('channel-lister-field.index'));

        //     // If soft deletes are implemented, the record should still exist but be soft deleted
        //     // If not, it should be completely removed
        //     $deletedField = ChannelListerField::withTrashed()->find($fieldId);

        //     if (method_exists(ChannelListerField::class, 'trashed')) {
        //         expect($deletedField)->not->toBeNull();
        //         expect($deletedField->trashed())->toBeTrue();
        //     } else {
        //         expect(ChannelListerField::find($fieldId))->toBeNull();
        //     }
        // });
    });

    describe('form validation edge cases', function (): void {
        it('handles boolean conversion for required field', function (): void {
            $data = [
                'ordering' => 1,
                'field_name' => 'test_field',
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT->value,
                'required' => '1', // String boolean
                'grouping' => 'basic',
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertRedirect(route('channel-lister-field.index'));

            $this->assertDatabaseHas('channel_lister_fields', [
                'field_name' => 'test_field',
                'required' => true,
            ]);
        });

        it('handles null values for optional fields', function (): void {
            $data = [
                'ordering' => 1,
                'field_name' => 'test_field',
                'display_name' => null,
                'tooltip' => null,
                'example' => null,
                'marketplace' => 'amazon',
                'input_type' => InputType::TEXT->value,
                'input_type_aux' => null,
                'required' => false,
                'grouping' => 'basic',
                'type' => Type::CUSTOM->value,
            ];

            $this->post(route('channel-lister-field.store'), $data)
                ->assertRedirect(route('channel-lister-field.index'));

            $this->assertDatabaseHas('channel_lister_fields', [
                'field_name' => 'test_field',
                'display_name' => null,
                'tooltip' => null,
                'example' => null,
                'input_type_aux' => null,
            ]);
        });
    });
});
