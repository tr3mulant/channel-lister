<?php

declare(strict_types=1);

use IGE\ChannelLister\Models\WishBrandDirectory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('WishBrandDirectory Model', function (): void {
    it('can be instantiated', function (): void {
        $brand = new WishBrandDirectory;

        expect($brand)->toBeInstanceOf(WishBrandDirectory::class);
    });

    it('uses HasFactory trait', function (): void {
        $traits = class_uses(WishBrandDirectory::class);

        expect($traits)->toContain(HasFactory::class);
    });

    it('has correct table name', function (): void {
        $brand = new WishBrandDirectory;

        expect($brand->getTable())->toBe('channel_lister_wish_brand_directory');
    });

    it('disables timestamps', function (): void {
        $brand = new WishBrandDirectory;

        expect($brand->timestamps)->toBeFalse();
    });

    it('has correct fillable attributes', function (): void {
        $brand = new WishBrandDirectory;
        $expectedFillable = [
            'brand_id',
            'brand_name',
            'brand_website_url',
        ];

        expect($brand->getFillable())->toBe($expectedFillable);
    });

    it('has correct casts', function (): void {
        $brand = new WishBrandDirectory;
        $expectedCasts = [
            'id' => 'int',
            'last_update' => 'datetime',
        ];

        expect($brand->getCasts())->toMatchArray($expectedCasts);
    });

    it('can be created with factory', function (): void {
        $brand = WishBrandDirectory::factory()->create();

        expect($brand)->toBeInstanceOf(WishBrandDirectory::class)
            ->and($brand->exists)->toBeTrue()
            ->and($brand->id)->toBeInt();
    });

    it('can be created with specific attributes', function (): void {
        $lastUpdateString = '2023-05-20';

        $attributes = [
            'brand_id' => 'APPLE123',
            'brand_name' => 'Apple Inc.',
            'brand_website_url' => 'https://www.apple.com',
            'last_update' => $lastUpdateString,
        ];

        $brand = WishBrandDirectory::factory()->create($attributes);

        expect($brand->brand_id)->toBe('APPLE123')
            ->and($brand->brand_name)->toBe('Apple Inc.')
            ->and($brand->brand_website_url)->toBe('https://www.apple.com')
            ->and($brand->last_update->format('Y-m-d'))->toBe($lastUpdateString);
    });
});

describe('WishBrandDirectory Scopes', function (): void {
    beforeEach(function (): void {
        // Create test data
        WishBrandDirectory::factory()->create([
            'brand_id' => 'APPLE123',
            'brand_name' => 'Apple Inc.',
            'brand_website_url' => 'https://www.apple.com',
            'last_update' => '2023-01-15',
        ]);

        WishBrandDirectory::factory()->create([
            'brand_id' => 'GOOGLE456',
            'brand_name' => 'Google LLC',
            'brand_website_url' => null,
            'last_update' => '2023-06-10',
        ]);

        WishBrandDirectory::factory()->create([
            'brand_id' => 'AMAZON789',
            'brand_name' => 'Amazon.com Inc.',
            'brand_website_url' => 'http://www.amazon.com',
            'last_update' => '2022-12-20',
        ]);

        WishBrandDirectory::factory()->create([
            'brand_id' => 'MICROSOFT001',
            'brand_name' => 'Microsoft Corporation',
            'brand_website_url' => 'https://microsoft.com',
            'last_update' => '2023-08-05',
        ]);
    });

    it('can scope by brand ID', function (): void {
        $appleBrand = WishBrandDirectory::byBrandId('apple123')->get();
        $googleBrand = WishBrandDirectory::byBrandId('GOOGLE456')->get();

        expect($appleBrand)->toHaveCount(1)
            ->and($googleBrand)->toHaveCount(1);

        expect($appleBrand->first()->brand_id)->toBe('APPLE123');
        expect($googleBrand->first()->brand_id)->toBe('GOOGLE456');
    });

    it('can scope brands with website', function (): void {
        $brandsWithWebsite = WishBrandDirectory::withWebsite()->get();

        expect($brandsWithWebsite)->toHaveCount(3);

        $brandsWithWebsite->each(function (WishBrandDirectory $brand): void {
            expect($brand->brand_website_url)->not->toBeNull();
        });
    });

    it('can scope brands without website', function (): void {
        $brandsWithoutWebsite = WishBrandDirectory::withoutWebsite()->get();

        expect($brandsWithoutWebsite)->toHaveCount(1);

        $brandsWithoutWebsite->each(function (WishBrandDirectory $brand): void {
            expect($brand->brand_website_url)->toBeNull();
        });
    });

    it('can scope brands updated after a date', function (): void {
        $recentBrands = WishBrandDirectory::updatedAfter('2023-01-01')->get();
        $veryRecentBrands = WishBrandDirectory::updatedAfter('2023-06-01')->get();

        expect($recentBrands)->toHaveCount(3)
            ->and($veryRecentBrands)->toHaveCount(2);

        $recentBrands->each(function (WishBrandDirectory $brand): void {
            expect($brand->last_update->format('Y-m-d'))->toBeGreaterThanOrEqual('2023-01-01');
        });
    });

    it('can scope brands updated before a date', function (): void {
        $olderBrands = WishBrandDirectory::updatedBefore('2023-06-30')->get();
        $veryOldBrands = WishBrandDirectory::updatedBefore('2022-12-31')->get();

        expect($olderBrands)->toHaveCount(3)
            ->and($veryOldBrands)->toHaveCount(1);

        $olderBrands->each(function (WishBrandDirectory $brand): void {
            expect($brand->last_update->format('Y-m-d'))->toBeLessThanOrEqual('2023-06-30');
        });
    });

    it('can scope brands by domain', function (): void {
        $appleBrands = WishBrandDirectory::byDomain('apple.com')->get();
        $amazonBrands = WishBrandDirectory::byDomain('amazon.com')->get();

        expect($appleBrands)->toHaveCount(1)
            ->and($amazonBrands)->toHaveCount(1);

        expect($appleBrands->first()->brand_name)->toBe('Apple Inc.');
        expect($amazonBrands->first()->brand_name)->toBe('Amazon.com Inc.');
    });

    it('can scope secure URLs only', function (): void {
        $secureBrands = WishBrandDirectory::secureUrls()->get();

        expect($secureBrands)->toHaveCount(3);

        $secureBrands->each(function (WishBrandDirectory $brand): void {
            expect($brand->brand_website_url)->toStartWith('https://');
        });
    });

    it('can chain scopes', function (): void {
        $chainedBrands = WishBrandDirectory::withWebsite()
            ->secureUrls()
            ->updatedAfter('2023-01-01')
            ->get();

        expect($chainedBrands)->toHaveCount(2);

        $chainedBrands->each(function (WishBrandDirectory $brand): void {
            expect($brand->brand_website_url)->not->toBeNull()
                ->and($brand->brand_website_url)->toStartWith('https://')
                ->and($brand->last_update->format('Y-m-d'))->toBeGreaterThanOrEqual('2023-01-01');
        });
    });
});

describe('WishBrandDirectory Methods', function (): void {
    it('can check if brand has website', function (): void {
        $brandWithWebsite = WishBrandDirectory::factory()->create([
            'brand_website_url' => 'https://example.com',
        ]);

        $brandWithoutWebsite = WishBrandDirectory::factory()->create([
            'brand_website_url' => null,
        ]);

        $brandWithEmptyWebsite = WishBrandDirectory::factory()->create([
            'brand_website_url' => '',
        ]);

        expect($brandWithWebsite->hasWebsite())->toBeTrue()
            ->and($brandWithoutWebsite->hasWebsite())->toBeFalse()
            ->and($brandWithEmptyWebsite->hasWebsite())->toBeFalse();
    });

    it('can generate slug from brand name', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_name' => 'Apple & Associates Plus',
        ]);

        expect($brand->getSlug())->toBe('apple-and-associates-plus');
    });

    it('returns null slug for empty brand name', function (): void {
        $brand = new WishBrandDirectory;
        $brand->brand_name = null;

        expect($brand->getSlug())->toBeNull();
    });

    it('handles special characters in slug generation', function (): void {
        $testCases = [
            'Apple Inc.' => 'apple-inc.',
            'AT&T Corporation' => 'at-and-t-corporation',
            'Google+' => 'googleplus',
            'Johnson & Johnson' => 'johnson-and-johnson',
            'Procter & Gamble' => 'procter-and-gamble',
        ];

        foreach ($testCases as $brandName => $expectedSlug) {
            $brand = new WishBrandDirectory;
            $brand->brand_name = $brandName;
            expect($brand->getSlug())->toBe($expectedSlug);
        }
    });
});

describe('WishBrandDirectory Attributes', function (): void {
    it('returns display brand name in title case', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_name' => 'apple inc.',
        ]);

        expect($brand->display_brand_name)->toBe('Apple Inc.');
    });

    it('formats brand website URL correctly when getting', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_website_url' => 'HTTPS://WWW.EXAMPLE.COM',
        ]);

        expect($brand->brand_website_url)->toBe('https://www.example.com');
    });

    it('adds https protocol when setting URL without protocol', function (): void {
        $brand = new WishBrandDirectory;
        $brand->brand_website_url = 'www.example.com';

        expect($brand->getAttributes()['brand_website_url'])->toBe('https://www.example.com');
    });

    it('forces https when setting URL', function (): void {
        $brand = new WishBrandDirectory;
        $brand->brand_website_url = 'http://www.example.com';

        expect($brand->getAttributes()['brand_website_url'])->toBe('https://www.example.com');
    });

    it('handles null URL when setting', function (): void {
        $brand = new WishBrandDirectory;
        $brand->brand_website_url = null;

        expect($brand->getAttributes()['brand_website_url'])->toBeNull();
    });

    it('trims whitespace when setting URL', function (): void {
        $brand = new WishBrandDirectory;
        $brand->brand_website_url = '  www.example.com  ';

        expect($brand->getAttributes()['brand_website_url'])->toBe('https://www.example.com');
    });

    it('extracts domain from URL', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_website_url' => 'https://www.example.com/path',
        ]);

        expect($brand->domain)->toBe('www.example.com');
    });

    it('returns null domain for brands without website', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_website_url' => null,
        ]);

        expect($brand->domain)->toBeNull();
    });

    it('handles malformed URLs when extracting domain', function (): void {
        $brand = new WishBrandDirectory;
        $brand->setRawAttributes(['brand_website_url' => 'not-a-valid-url']);

        expect($brand->domain)->toBeNull();
    });
});

describe('WishBrandDirectory Datetime Casting', function (): void {
    it('casts last_update to Carbon instance', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'last_update' => '2023-05-20 14:45:30',
        ]);

        expect($brand->last_update)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($brand->last_update->format('Y-m-d'))->toBe('2023-05-20');
    });

    it('handles different date formats', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'last_update' => '2023-01-01 00:00:00',
        ]);

        expect($brand->last_update->year)->toBe(2023)
            ->and($brand->last_update->month)->toBe(1)
            ->and($brand->last_update->day)->toBe(1);
    });
});

describe('WishBrandDirectory Factory States', function (): void {
    it('can create brand with website using factory state', function (): void {
        $brand = WishBrandDirectory::factory()->withWebsite()->create();

        expect($brand->hasWebsite())->toBeTrue()
            ->and($brand->brand_website_url)->not->toBeNull();
    });

    it('can create brand without website using factory state', function (): void {
        $brand = WishBrandDirectory::factory()->withoutWebsite()->create();

        expect($brand->hasWebsite())->toBeFalse()
            ->and($brand->brand_website_url)->toBeNull();
    });

    it('can create brand with secure URL using factory state', function (): void {
        $brand = WishBrandDirectory::factory()->withSecureUrl()->create();

        expect($brand->brand_website_url)->toStartWith('https://');
    });

    it('can create brand with insecure URL using factory state that forces https', function (): void {
        $brand = WishBrandDirectory::factory()->withInsecureUrl()->create();

        expect($brand->brand_website_url)->toStartWith('https://');
    });

    it('can create brand with specific domain using factory state', function (): void {
        $brand = WishBrandDirectory::factory()->withDomain('example.com')->create();

        expect($brand->domain)->toBe('example.com');
    });

    it('can create recently updated brand using factory state', function (): void {
        $brand = WishBrandDirectory::factory()->recentlyUpdated()->create();
        $oneWeekAgo = now()->subWeek();

        expect($brand->last_update)->toBeGreaterThan($oneWeekAgo);
    });

    it('can chain factory states', function (): void {
        $brand = WishBrandDirectory::factory()
            ->withSecureUrl()
            ->recentlyUpdated()
            ->create();

        $oneWeekAgo = now()->subWeek();

        expect($brand->brand_website_url)->toStartWith('https://')
            ->and($brand->last_update)->toBeGreaterThan($oneWeekAgo);
    });
});

describe('WishBrandDirectory Validation', function (): void {
    it('requires brand_id', function (): void {
        expect(function (): void {
            WishBrandDirectory::factory()->create([
                'brand_id' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires brand_name', function (): void {
        expect(function (): void {
            WishBrandDirectory::factory()->create([
                'brand_name' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires last_update', function (): void {
        expect(function (): void {
            WishBrandDirectory::factory()->create([
                'last_update' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('allows null brand_website_url', function (): void {
        $brand = WishBrandDirectory::factory()->create([
            'brand_website_url' => null,
        ]);

        expect($brand->brand_website_url)->toBeNull();
    });
});

describe('WishBrandDirectory Search Scopes', function (): void {
    beforeEach(function (): void {
        // Note: These tests would require FULLTEXT indexes in a real database
        // For testing purposes, we'll skip the actual FULLTEXT search tests
        // and focus on testing that the methods exist and can be called
    });

    it('has searchByName scope method', function (): void {
        $brand = new WishBrandDirectory;
        $query = $brand->newQuery();

        expect(method_exists($brand, 'scopeSearchByName'))->toBeTrue();

        // Test that calling the scope doesn't throw an error
        // In a real test with proper FULLTEXT indexes, this would work
        expect(function () use ($query): void {
            $query->searchByName('Apple');
        })->not->toThrow(Exception::class);
    });

    it('has searchByNameBoolean scope method', function (): void {
        $brand = new WishBrandDirectory;
        $query = $brand->newQuery();

        expect(method_exists($brand, 'scopeSearchByNameBoolean'))->toBeTrue();

        // Test that calling the scope doesn't throw an error
        expect(function () use ($query): void {
            $query->searchByNameBoolean('+Apple -Inc');
        })->not->toThrow(Exception::class);
    });
});
