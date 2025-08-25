<?php

declare(strict_types=1);

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;

describe('ChannelListerController API', function (): void {
    beforeEach(function (): void {
        // Create test data for platform validation
        ChannelListerField::create([
            'ordering' => 1,
            'marketplace' => 'amazon',
            'field_name' => 'title',
            'input_type' => InputType::TEXT,
            'type' => Type::CUSTOM,
            'required' => true,
            'grouping' => 'general',
        ]);

        ChannelListerField::create([
            'ordering' => 2,
            'marketplace' => 'ebay',
            'field_name' => 'description',
            'input_type' => InputType::TEXTAREA,
            'type' => Type::CUSTOM,
            'required' => false,
            'grouping' => 'general',
        ]);
    });

    describe('buildModalView', function (): void {
        it('returns successful JSON response with modal header component', function (): void {
            $response = $this->getJson(route('api.channel-lister.build-modal-view'));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                ]);

            expect($response->json('data'))->toBeString();
        });
    });

    describe('formDataByPlatform', function (): void {
        it('returns form data for valid platform', function (): void {
            $response = $this->getJson(route('api.channel-lister.get-form-data-by-platform', ['platform' => 'amazon']));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                ]);

            expect($response->json('data'))->toBeString();
        });

        it('returns validation error for non-existent platform', function (): void {
            $response = $this->getJson(route('api.channel-lister.get-form-data-by-platform', ['platform' => 'invalid-platform']));

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform']);
        });

        it('returns validation error for empty platform', function (): void {
            $response = $this->getJson(url('channel-lister/get-form-data-by-platform'));

            $response->assertStatus(404);
        });

        it('works with multiple platforms', function (): void {
            $platforms = ['amazon', 'ebay'];

            foreach ($platforms as $platform) {
                $response = $this->getJson(route('api.channel-lister.get-form-data-by-platform', ['platform' => $platform]));

                $response->assertStatus(200)
                    ->assertJsonStructure([
                        'data',
                    ]);

                expect($response->json('data'))->toBeString();
            }
        });
    });

    describe('buildUpc', function (): void {
        it('generates UPC without prefix', function (): void {
            $response = $this->getJson(route('api.channel-lister.build-upc'));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'prefix',
                    'is_purchased',
                    'name',
                ]);

            $responseData = $response->json();
            expect($responseData['data'])->toBeString();
            expect($responseData['prefix'])->toBe('');
            expect($responseData['is_purchased'])->toBeBool();
            expect($responseData['name'])->toBeNull();
            expect(strlen((string) $responseData['data']))->toBe(12); // UPC should be 12 digits
        });

        it('generates UPC with valid prefix', function (): void {
            $prefix = '123456';

            $response = $this->getJson(route('api.channel-lister.build-upc', ['prefix' => $prefix]));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'prefix',
                    'is_purchased',
                    'name',
                ]);

            $responseData = $response->json();
            expect($responseData['data'])->toBeString();
            expect($responseData['prefix'])->toBe($prefix);
            expect($responseData['is_purchased'])->toBeBool();
            expect(strlen((string) $responseData['data']))->toBe(12);
            expect($responseData['data'])->toStartWith($prefix);
        });

        it('returns error for prefix that is too long', function (): void {
            $longPrefix = '123456789012'; // 12 characters, too long

            $response = $this->getJson(route('api.channel-lister.build-upc', ['prefix' => $longPrefix]));

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'prefix',
                    ],
                ]);

            expect($response->json('errors.prefix')[0])->toContain('must not be greater than 11 characters');
        });

        it('returns error for non-numeric prefix', function (): void {
            $nonNumericPrefix = '12345a';

            $response = $this->getJson(route('api.channel-lister.build-upc', ['prefix' => $nonNumericPrefix]));

            $response->assertStatus(400)
                ->assertJsonStructure([
                    'error',
                ]);

            expect($response->json('error'))->toContain('must be only digits');
        });

        it('validates prefix length', function (): void {
            $validPrefixes = ['1', '12', '123', '1234567890', '12345678901']; // 1-11 characters

            foreach ($validPrefixes as $prefix) {
                $response = $this->getJson(route('api.channel-lister.build-upc', ['prefix' => $prefix]));

                $response->assertStatus(200);
                expect($response->json('data'))->toStartWith($prefix);
            }
        });

        it('handles empty prefix parameter', function (): void {
            $response = $this->getJson(route('api.channel-lister.build-upc', ['prefix' => '']));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'prefix',
                    'is_purchased',
                    'name',
                ]);

            expect($response->json('prefix'))->toBe('');
        });
    });

    describe('isUpcValid', function (): void {
        it('validates correct UPC', function (): void {
            // First generate a valid UPC
            $upcResponse = $this->get(route('api.channel-lister.build-upc'));
            $validUpc = $upcResponse->json('data');

            $response = $this->getJson(route('api.channel-lister.is-upc-valid', ['UPC' => $validUpc]))
                ->assertStatus(200);

            expect($response->json())->toBeBool();
        });

        it('validates known invalid UPC', function (): void {
            $invalidUpc = '123456789012'; // Invalid checksum

            $response = $this->getJson(route('api.channel-lister.is-upc-valid', ['UPC' => $invalidUpc]))
                ->assertStatus(200);

            expect($response->json())->toBeBool();
        });

        it('requires UPC parameter', function (): void {
            $response = $this->getJson(route('api.channel-lister.is-upc-valid'));

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['UPC']);
        });

        it('validates UPC parameter as string', function (): void {
            $response = $this->getJson(route('api.channel-lister.is-upc-valid', ['UPC' => '']));

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['UPC']);
        });

        it('handles various UPC formats', function (): void {
            $testCases = [
                '123456789012' => true,
                '000000000000' => true,
                '999999999999' => false,
                '12345678901' => false, // 11 digits
                '1234567890123' => false, // 13 digits
            ];

            foreach ($testCases as $upc => $expected) {
                $response = $this->getJson(route('api.channel-lister.is-upc-valid', ['UPC' => $upc]))
                    ->assertStatus(200);

                $actual = filter_var($response->getContent(), FILTER_VALIDATE_BOOLEAN);

                expect($actual)->toBe($expected);
            }
        });
    });

    describe('error handling', function (): void {
        it('handles malformed JSON gracefully', function (): void {
            $response = $this->json('GET', route('api.channel-lister.build-upc'), [], [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

            $response->assertStatus(200);
        });

        it('returns proper error format for validation failures', function (): void {
            $response = $this->getJson(route('api.channel-lister.get-form-data-by-platform', ['platform' => 'invalid']));

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'platform',
                    ],
                ]);
        });
    });

    describe('integration tests', function (): void {
        it('can build UPC and then validate it', function (): void {
            // Build a UPC
            $buildResponse = $this->getJson(route('api.channel-lister.build-upc'));
            $buildResponse->assertStatus(200);

            $upc = $buildResponse->json('data');

            // Validate the generated UPC
            $validateResponse = $this->getJson(route('api.channel-lister.is-upc-valid', ['UPC' => $upc]));
            $validateResponse->assertStatus(200);

            expect($validateResponse->json())->toBeTrue();
        });

        it('can get form data for all available platforms', function (): void {
            $platforms = ChannelListerField::distinct('marketplace')->pluck('marketplace');

            foreach ($platforms as $platform) {
                $response = $this->getJson(route('api.channel-lister.get-form-data-by-platform', ['platform' => $platform]));

                $response->assertStatus(200)
                    ->assertJsonStructure(['data']);
            }
        });
    });
});
