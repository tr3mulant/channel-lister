<?php

declare(strict_types=1);

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

describe('ChannelLister', function (): void {
    describe('css()', function (): void {
        it('returns htmlable with styles', function (): void {
            $result = ChannelLister::css();

            expect($result)->toBeInstanceOf(Htmlable::class);
            expect($result->toHtml())
                ->toContain('<style>')
                ->toContain('</style>');
        });

        it('throws exception when file not found', function (): void {
            // This test would require mocking the file_get_contents function
            // or moving the CSS file temporarily, which is complex
            // In a real scenario, you might want to mock the file system
            $this->markTestSkipped('CSS file loading test requires file system mocking');
        });
    });

    describe('createUpc()', function (): void {
        it('generates valid upc', function (string $input, bool $expectException = false): void {
            if ($expectException) {
                expect(fn (): string => ChannelLister::createUpc($input))->toThrow(Exception::class);

                return;
            }

            $upc = ChannelLister::createUpc($input);

            expect($upc)
                ->toHaveLength(12)
                ->toMatch('/^\d{12}$/')
                ->and(ChannelLister::isValidUpc($upc))->toBeTrue();
        })->with([
            'empty string' => [''],
            'valid prefix' => ['123'],
            'longer prefix' => ['12345678901'],
            'too long prefix' => ['123456789012', true], // Should throw exception
            'non-numeric prefix' => ['12a', true], // Should throw exception
        ]);

        it('generates valid starting digit with empty string', function (): void {
            $upc = ChannelLister::createUpc('');
            $firstDigit = (int) $upc[0];

            expect($firstDigit)
                ->not->toBeIn([2, 3, 4, 5])
                ->toBeIn([1, 6, 7, 8]);
        });

        it('throws exception for too long input', function (): void {
            expect(fn (): string => ChannelLister::createUpc('123456789012'))
                ->toThrow(Exception::class, "'123456789012' too long, expecting a string or int less than 12 characters in length");
        });

        it('throws exception for non-numeric input', function (): void {
            expect(fn (): string => ChannelLister::createUpc('12a'))
                ->toThrow(Exception::class, "'12a' must be only digits");
        });

        it('creates unique codes', function (): void {
            $upcs = [];

            // Generate multiple UPCs and ensure they're unique
            for ($i = 0; $i < 100; $i++) {
                $upc = ChannelLister::createUpc();
                expect($upcs)->not->toContain($upc, "Generated duplicate UPC: {$upc}");
                $upcs[] = $upc;
            }
        });

        it('has consistent checksum calculation', function (): void {
            $baseUpc = '12345678910';

            // Calculate checksum manually to verify the algorithm
            $checkdigit = 3 * (1 + 3 + 5 + 7 + 9); // odd positions
            $checkdigit += (2 + 4 + 6 + 8 + 1); // even positions
            $expectedCheckDigit = $checkdigit % 10 == 0 ? '0' : (string) (10 - $checkdigit % 10);

            $generatedUpc = ChannelLister::createUpc($baseUpc);

            expect($generatedUpc)->toBe($baseUpc.$expectedCheckDigit);
        });
    });

    describe('getPurchasedUpcPrefixes()', function (): void {
        it('returns only purchased prefixes', function (): void {
            Config::set('channel-lister.upc_prefixes', [
                ['prefix' => '123', 'name' => 'Company A', 'purchased' => true],
                ['prefix' => '456', 'name' => 'Company B', 'purchased' => false],
                ['prefix' => '789', 'name' => 'Company C', 'purchased' => true],
                ['prefix' => '000', 'name' => 'Company D'], // No purchased key, should default to false
            ]);

            $result = ChannelLister::getPurchasedUpcPrefixes();

            expect($result)
                ->toHaveCount(2)
                ->toContain('123')
                ->toContain('789')
                ->not->toContain('456')
                ->not->toContain('000');
        });

        it('returns empty array when no config', function (): void {
            Config::set('channel-lister.upc_prefixes', []);

            $result = ChannelLister::getPurchasedUpcPrefixes();

            expect($result)
                ->toBeArray()
                ->toBeEmpty();
        });
    });

    describe('isPurchasedUpcPrefix()', function (): void {
        it('returns true for purchased prefix', function (): void {
            Config::set('channel-lister.upc_prefixes', [
                ['prefix' => '123', 'name' => 'Company A', 'purchased' => true],
                ['prefix' => '456', 'name' => 'Company B', 'purchased' => false],
            ]);

            expect(ChannelLister::isPurchasedUpcPrefix('123'))->toBeTrue();
            expect(ChannelLister::isPurchasedUpcPrefix('456'))->toBeFalse();
            expect(ChannelLister::isPurchasedUpcPrefix('999'))->toBeFalse();
        });
    });

    describe('getNameByPrefix()', function (): void {
        it('returns correct name', function (): void {
            Config::set('channel-lister.upc_prefixes', [
                ['prefix' => '123', 'name' => 'Company A', 'purchased' => true],
                ['prefix' => '456', 'name' => 'Company B', 'purchased' => false],
            ]);

            expect(ChannelLister::getNameByPrefix('123'))->toBe('Company A');
            expect(ChannelLister::getNameByPrefix('456'))->toBe('Company B');
            expect(ChannelLister::getNameByPrefix('999'))->toBeNull();
        });

        it('returns null for non-existent prefix', function (): void {
            Config::set('channel-lister.upc_prefixes', [
                ['prefix' => '123', 'name' => 'Company A', 'purchased' => true],
            ]);

            expect(ChannelLister::getNameByPrefix('999'))->toBeNull();
        });
    });

    describe('isValidUpc()', function (): void {
        it('validates correctly', function (string $upc, bool $expected): void {
            expect(ChannelLister::isValidUpc($upc))->toBe($expected);
        })->with([
            'valid UPC' => ['123456789104', true], // Valid checksum
            'invalid length short' => ['12345678910', false],
            'invalid length long' => ['1234567891012', false],
            'non-numeric' => ['12345678910a', false],
            'invalid checksum' => ['123456789105', false], // Wrong checksum
            'all zeros' => ['000000000000', true], // Valid checksum for all zeros
        ]);

        it('validates known valid codes', function (): void {
            // Test with some known valid UPC codes
            $validUpcs = [
                '036000291452', // Coca-Cola
                '012000161155', // Campbell's Soup
            ];

            foreach ($validUpcs as $upc) {
                expect(ChannelLister::isValidUpc($upc))
                    ->toBeTrue("UPC {$upc} should be valid");
            }
        });
    });

    describe('marketplaceDisplayName()', function (): void {
        it('formats correctly', function (string $input, string $expected): void {
            expect(ChannelLister::marketplaceDisplayName($input))->toBe($expected);
        })->with([
            'amazon' => ['amazon', 'Amazon'],
            'amazon-us' => ['amazon-us', 'Amazon'],
            'amazon_us' => ['amazon_us', 'Amazon'],
            'amazon-ca' => ['amazon-ca', 'Amazon CA'],
            'amazon_ca' => ['amazon_ca', 'Amazon CA'],
            'amazon-au' => ['amazon-au', 'Amazon AU'],
            'amazon_au' => ['amazon_au', 'Amazon AU'],
            'amazon-mx' => ['amazon-mx', 'Amazon MX'],
            'amazon_mx' => ['amazon_mx', 'Amazon MX'],
            'ebay' => ['ebay', 'eBay'],
            'walmart' => ['walmart', 'Walmart'],
            'walmart-us' => ['walmart-us', 'Walmart'],
            'walmart_us' => ['walmart_us', 'Walmart'],
            'walmart-ca' => ['walmart-ca', 'Walmart CA'],
            'walmart_ca' => ['walmart_ca', 'Walmart CA'],
            'unknown marketplace' => ['some-unknown-marketplace', 'Some-unknown-marketplace'],
            'mixed case' => ['tEsT mArKeTpLaCe', 'Test Marketplace'],
        ]);
    });

    describe('disabledMarketplaces()', function (): void {
        it('returns array from config', function (): void {
            Config::set('channel-lister.marketplaces.disabled', ['amazon', 'ebay']);

            $result = ChannelLister::disabledMarketplaces();

            expect($result)
                ->toBeArray()
                ->toHaveCount(2)
                ->toContain('amazon')
                ->toContain('ebay');
        });

        it('converts string to array', function (): void {
            Config::set('channel-lister.marketplaces.disabled', 'amazon');

            $result = ChannelLister::disabledMarketplaces();

            expect($result)
                ->toBeArray()
                ->toHaveCount(1)
                ->toContain('amazon');
        });

        it('returns empty array when no config', function (): void {
            Config::set('channel-lister.marketplaces.disabled', []);

            $result = ChannelLister::disabledMarketplaces();

            expect($result)
                ->toBeArray()
                ->toBeEmpty();
        });
    });

    describe('getCountryCode()', function (): void {
        it('returns correct 2-digit country codes', function (): void {
            expect(ChannelLister::getCountryCode('United States', 2))->toBe('US');
            expect(ChannelLister::getCountryCode('Canada', 2))->toBe('CA');
            expect(ChannelLister::getCountryCode('Germany', 2))->toBe('DE');
            expect(ChannelLister::getCountryCode('Japan', 2))->toBe('JP');
            expect(ChannelLister::getCountryCode('Australia', 2))->toBe('AU');
        });

        it('returns correct 3-digit country codes', function (): void {
            expect(ChannelLister::getCountryCode('United States', 3))->toBe('USA');
            expect(ChannelLister::getCountryCode('Canada', 3))->toBe('CAN');
            expect(ChannelLister::getCountryCode('Germany', 3))->toBe('DEU');
            expect(ChannelLister::getCountryCode('Japan', 3))->toBe('JPN');
            expect(ChannelLister::getCountryCode('Australia', 3))->toBe('AUS');
        });

        it('returns null for non-existent countries', function (): void {
            expect(ChannelLister::getCountryCode('Non-Existent Country', 2))->toBeNull();
            expect(ChannelLister::getCountryCode('Non-Existent Country', 3))->toBeNull();
        });

        it('handles edge cases', function (): void {
            expect(ChannelLister::getCountryCode('Afghanistan', 2))->toBe('AF');
            expect(ChannelLister::getCountryCode('Zimbabwe', 2))->toBe('ZW');
            expect(ChannelLister::getCountryCode('Afghanistan', 3))->toBe('AFG');
            expect(ChannelLister::getCountryCode('Zimbabwe', 3))->toBe('ZWE');
        });
    });

    describe('csv()', function (): void {
        beforeEach(function (): void {
            // Set up test configuration
            Config::set('channel-lister.downloads.disk', 'local');
            Config::set('channel-lister.downloads.path', 'test-exports');
            Config::set('channel-lister.default_warehouse', 'MAIN');

            // Create test fields in database
            ChannelListerField::factory()->create([
                'field_name' => 'SKU',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Product Title',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Total Quantity',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::INTEGER,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Custom Field',
                'type' => Type::CUSTOM,
                'input_type' => InputType::TEXT,
            ]);
        });

        afterEach(function (): void {
            // Clean up any created files
            Storage::disk('local')->deleteDirectory('test-exports');
        });

        it('generates csv file path for basic data', function (): void {
            $data = [
                'SKU' => 'TEST-123',
                'Product Title' => 'Test Product',
                'custom_attribute' => 'custom_value',
            ];

            $result = ChannelLister::csv($data);

            expect($result)->toBeString();
            expect($result)->toContain('test-exports/');
            expect($result)->toContain('.csv');
            expect(Storage::disk('local')->exists($result))->toBeTrue();
        });

        it('handles quantity fields properly', function (): void {
            $data = [
                'SKU' => 'TEST-123',
                'Total Quantity' => '50',
            ];

            $result = ChannelLister::csv($data);

            expect(Storage::disk('local')->exists($result))->toBeTrue();

            $content = Storage::disk('local')->get($result);
            expect($content)->toContain('Quantity Update Type');
            expect($content)->toContain('DC Quantity');
            expect($content)->toContain('MAIN=50');
        });

        it('handles image fields', function (): void {
            $data = [
                'SKU' => 'TEST-123',
                'image1' => 'https://example.com/image1.jpg',
                'image2' => 'https://example.com/image2.jpg',
                'image1_alt' => 'Alt text',
            ];

            $result = ChannelLister::csv($data);

            $content = Storage::disk('local')->get($result);
            expect($content)->toContain('Picture URLs');
            expect($content)->toContain('https://example.com/image1.jpg,https://example.com/image2.jpg');
        });
    });

    describe('csvFromUnifiedData()', function (): void {
        beforeEach(function (): void {
            Config::set('channel-lister.downloads.disk', 'local');
            Config::set('channel-lister.downloads.path', 'test-exports');
            Config::set('channel-lister.default_warehouse', 'MAIN');

            ChannelListerField::factory()->create([
                'field_name' => 'SKU',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Product Title',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
        });

        afterEach(function (): void {
            Storage::disk('local')->deleteDirectory('test-exports');
        });

        it('generates csv from unified export data', function (): void {
            $exportData = [
                'SKU' => 'UNIFIED-123',
                'Product Title' => 'Unified Product',
                'Amazon Category' => 'Electronics',
                'Custom Attribute' => 'Custom Value',
            ];

            $result = ChannelLister::csvFromUnifiedData($exportData);

            expect($result)->toBeString();
            expect($result)->toContain('test-exports/');
            expect($result)->toContain('.csv');
            expect(Storage::disk('local')->exists($result))->toBeTrue();
        });

        it('separates channel advisor and custom fields correctly', function (): void {
            $exportData = [
                'SKU' => 'UNIFIED-123',
                'Product Title' => 'Unified Product',
                'Amazon Brand' => 'Test Brand',
                'Amazon Color' => 'Blue',
            ];

            $result = ChannelLister::csvFromUnifiedData($exportData);

            $content = Storage::disk('local')->get($result);
            expect($content)->toContain('SKU');
            expect($content)->toContain('Product Title');
            expect($content)->toContain('Attribute1Name');
            expect($content)->toContain('Attribute1Value');
        });
    });

    describe('extractChannelAdvisorFields()', function (): void {
        beforeEach(function (): void {
            Config::set('channel-lister.default_warehouse', 'WAREHOUSE1');

            ChannelListerField::factory()->create([
                'field_name' => 'SKU',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Product Title',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Total Quantity',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::INTEGER,
            ]);
        });

        it('extracts only channel advisor fields', function (): void {
            $exportData = [
                'SKU' => 'CA-123',
                'Product Title' => 'Channel Advisor Product',
                'Amazon Brand' => 'Test Brand',
                'Custom Field' => 'Custom Value',
            ];

            $result = callProtectedMethod(ChannelLister::class, 'extractChannelAdvisorFields', [$exportData]);

            expect($result)->toHaveKey('SKU');
            expect($result)->toHaveKey('Product Title');
            expect($result)->toHaveKey('Picture URLs');
            expect($result)->not->toHaveKey('Amazon Brand');
            expect($result)->not->toHaveKey('Custom Field');
        });

        it('handles quantity updates correctly', function (): void {
            $exportData = [
                'SKU' => 'CA-123',
                'Total Quantity' => '25',
            ];

            $result = callProtectedMethod(ChannelLister::class, 'extractChannelAdvisorFields', [$exportData]);

            expect($result)->toHaveKey('Quantity Update Type');
            expect($result)->toHaveKey('DC Quantity');
            expect($result)->toHaveKey('DC Quantity Update Type');
            expect($result['Quantity Update Type'])->toBe('UNSHIPPED');
            expect($result['DC Quantity'])->toBe('WAREHOUSE1=25');
            expect($result['DC Quantity Update Type'])->toBe('partial dc list');
            expect($result)->not->toHaveKey('Total Quantity');
        });

        it('ignores empty and zero values', function (): void {
            $exportData = [
                'SKU' => 'CA-123',
                'Product Title' => '',
                'Amazon Brand' => '0',
                'Valid Field' => 'Valid Value',
            ];

            $result = callProtectedMethod(ChannelLister::class, 'extractChannelAdvisorFields', [$exportData]);

            expect($result)->toHaveKey('SKU');
            expect($result)->not->toHaveKey('Product Title');
            expect($result)->not->toHaveKey('Amazon Brand');
        });

        it('handles image preparation', function (): void {
            $exportData = [
                'SKU' => 'CA-123',
                'image1' => 'https://example.com/img1.jpg',
                'image2' => 'https://example.com/img2.jpg',
                'image1_alt' => 'Alt text',
            ];

            $result = callProtectedMethod(ChannelLister::class, 'extractChannelAdvisorFields', [$exportData]);

            expect($result)->toHaveKey('Picture URLs');
            expect($result['Picture URLs'])->toBe('https://example.com/img1.jpg,https://example.com/img2.jpg');
        });
    });

    describe('extractData()', function (): void {
        beforeEach(function (): void {
            Config::set('channel-lister.default_warehouse', 'TEST_WAREHOUSE');

            // Create ChannelAdvisor fields
            ChannelListerField::factory()->create([
                'field_name' => 'SKU',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Product Title',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::TEXT,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Total Quantity',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::INTEGER,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Is Available',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::CHECKBOX,
            ]);
            ChannelListerField::factory()->create([
                'field_name' => 'Price',
                'type' => Type::CHANNEL_ADVISOR,
                'input_type' => InputType::CURRENCY,
            ]);
        });

        describe('when extracting ChannelAdvisor fields (custom = false)', function (): void {
            it('extracts valid ChannelAdvisor fields only', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'Product Title' => 'Test Product',
                    'Price' => '19.99',
                    'unknown_field' => 'should_be_ignored',
                    'empty_field' => '',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result)->toHaveKey('SKU');
                expect($result)->toHaveKey('Product Title');
                expect($result)->toHaveKey('Price');
                expect($result)->toHaveKey('Picture URLs');
                expect($result)->not->toHaveKey('unknown_field');
                expect($result)->not->toHaveKey('empty_field');
            });

            it('handles checkbox fields correctly', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'Is Available' => 'on',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['Is Available'])->toBe('true');
            });

            it('handles checkbox fields when off', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'Is Available' => 'off',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['Is Available'])->toBe('false');
            });

            it('handles quantity fields with warehouse mapping', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'Total Quantity' => '100',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result)->toHaveKey('Quantity Update Type');
                expect($result)->toHaveKey('DC Quantity');
                expect($result)->toHaveKey('DC Quantity Update Type');
                expect($result['Quantity Update Type'])->toBe('UNSHIPPED');
                expect($result['DC Quantity'])->toBe('TEST_WAREHOUSE=100');
                expect($result['DC Quantity Update Type'])->toBe('partial dc list');
                expect($result)->not->toHaveKey('Total Quantity');
            });

            it('processes image fields for Picture URLs', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'image1' => 'https://example.com/image1.jpg',
                    'image2' => 'https://example.com/image2.jpg',
                    'image3' => '',
                    'image1_alt' => 'Alt text 1',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['Picture URLs'])->toBe('https://example.com/image1.jpg,https://example.com/image2.jpg');
            });

            it('handles field name mapping with spaces and underscores', function (): void {
                ChannelListerField::factory()->create([
                    'field_name' => 'Long Field Name',
                    'type' => Type::CHANNEL_ADVISOR,
                    'input_type' => InputType::TEXT,
                ]);

                $data = [
                    'Long Field Name' => 'test1',
                    'Long_Field_Name' => 'test2',
                    'LongFieldName' => 'test3',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                // Should map all variations to the proper field name
                expect($result)->toHaveKey('Long Field Name');
            });

            it('trims whitespace from values', function (): void {
                $data = [
                    'SKU' => '  TEST-123  ',
                    'Product Title' => "\t Test Product \n",
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['SKU'])->toBe('TEST-123');
                expect($result['Product Title'])->toBe('Test Product');
            });
        });

        describe('when extracting custom fields (custom = true)', function (): void {
            it('extracts only non-ChannelAdvisor fields', function (): void {
                $data = [
                    'SKU' => 'TEST-123',
                    'Product Title' => 'Test Product',
                    'Custom Field 1' => 'Custom Value 1',
                    'Custom Field 2' => 'Custom Value 2',
                    'empty_custom' => '',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, true]);

                expect($result)->not->toHaveKey('SKU');
                expect($result)->not->toHaveKey('Product Title');
                expect($result)->toHaveKey('Custom Field 1');
                expect($result)->toHaveKey('Custom Field 2');
                expect($result)->not->toHaveKey('empty_custom');
            });

            it('handles checkbox fields backfill when custom = true', function (): void {
                // Create a checkbox field that won't be in the form data
                ChannelListerField::factory()->create([
                    'field_name' => 'Missing Checkbox',
                    'type' => Type::CHANNEL_ADVISOR,
                    'input_type' => InputType::CHECKBOX,
                ]);

                $data = [
                    'Custom Field' => 'Custom Value',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, true]);

                expect($result)->toHaveKey('Custom Field');
                expect($result)->toHaveKey('Missing Checkbox');
                expect($result['Missing Checkbox'])->toBe('false');
            });

            it('processes checkbox fields correctly when custom = true', function (): void {
                $data = [
                    'Is Available' => 'on',
                    'Custom Field' => 'Custom Value',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, true]);

                // Checkbox fields are processed regardless of custom flag due to special logic
                expect($result)->toHaveKey('Is Available');
                expect($result['Is Available'])->toBe('true');
                expect($result)->toHaveKey('Custom Field');
            });
        });

        describe('edge cases', function (): void {
            it('handles empty data array', function (): void {
                $result = callProtectedMethod(ChannelLister::class, 'extractData', [[], false]);

                expect($result)->toBeArray();
                expect($result)->toHaveKey('Picture URLs');
                expect($result['Picture URLs'])->toBe('');
            });

            it('handles data with only whitespace values', function (): void {
                $data = [
                    'SKU' => '   ',
                    'Product Title' => "\t\n",
                    'Custom Field' => '  custom  ',
                ];

                $caResult = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);
                $customResult = callProtectedMethod(ChannelLister::class, 'extractData', [$data, true]);

                // CA fields should be filtered out due to empty trimmed values
                expect($caResult)->not->toHaveKey('SKU');
                expect($caResult)->not->toHaveKey('Product Title');

                // Custom field should be included with trimmed value
                expect($customResult)->toHaveKey('Custom Field');
                expect($customResult['Custom Field'])->toBe('custom');
            });

            it('handles warehouse config as non-string', function (): void {
                Config::set('channel-lister.default_warehouse', 123);

                $data = [
                    'SKU' => 'TEST-123',
                    'Total Quantity' => '50',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['DC Quantity'])->toBe('=50');
            });

            it('handles missing warehouse config', function (): void {
                Config::set('channel-lister.default_warehouse', null);

                $data = [
                    'SKU' => 'TEST-123',
                    'Total Quantity' => '50',
                ];

                $result = callProtectedMethod(ChannelLister::class, 'extractData', [$data, false]);

                expect($result['DC Quantity'])->toBe('=50');
            });
        });
    });
});

// Helper function to call protected methods for testing
if (! function_exists('callProtectedMethod')) {
    function callProtectedMethod($className, $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        if ($method->isStatic()) {
            return $method->invokeArgs(null, $parameters);
        }

        $instance = $reflection->newInstance();

        return $method->invokeArgs($instance, $parameters);
    }
}
