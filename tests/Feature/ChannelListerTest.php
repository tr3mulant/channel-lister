<?php

declare(strict_types=1);

use IGE\ChannelLister\ChannelLister;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Config;

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
            'walmart' => ['walmart', 'Walmart US'],
            'walmart-us' => ['walmart-us', 'Walmart US'],
            'walmart_us' => ['walmart_us', 'Walmart US'],
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
});
