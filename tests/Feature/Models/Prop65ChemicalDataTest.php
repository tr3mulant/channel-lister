<?php

declare(strict_types=1);

use IGE\ChannelLister\Models\Prop65ChemicalData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Prop65ChemicalData Model', function (): void {
    it('can be instantiated', function (): void {
        $chemical = new Prop65ChemicalData;

        expect($chemical)->toBeInstanceOf(Prop65ChemicalData::class);
    });

    it('uses HasFactory trait', function (): void {
        $traits = class_uses(Prop65ChemicalData::class);

        expect($traits)->toContain(HasFactory::class);
    });

    it('has correct table name', function (): void {
        $chemical = new Prop65ChemicalData;

        expect($chemical->getTable())->toBe('channel_lister_prop65_chemical_data');
    });

    it('disables timestamps', function (): void {
        $chemical = new Prop65ChemicalData;

        expect($chemical->timestamps)->toBeFalse();
    });

    it('has correct fillable attributes', function (): void {
        $chemical = new Prop65ChemicalData;
        $expectedFillable = [
            'chemical',
            'type_of_toxicity',
            'listing_mechanism',
            'cas_no',
            'date_listed',
            'nsrl_or_madl',
        ];

        expect($chemical->getFillable())->toBe($expectedFillable);
    });

    it('has correct casts', function (): void {
        $chemical = new Prop65ChemicalData;
        $expectedCasts = [
            'id' => 'int',
            'date_listed' => 'datetime',
            'last_update' => 'datetime',
        ];

        expect($chemical->getCasts())->toMatchArray($expectedCasts);
    });

    it('can be created with factory', function (): void {
        $chemical = Prop65ChemicalData::factory()->create();

        expect($chemical)->toBeInstanceOf(Prop65ChemicalData::class)
            ->and($chemical->exists)->toBeTrue()
            ->and($chemical->id)->toBeInt();
    });

    it('can be created with specific attributes', function (): void {
        $dateListedString = '2020-01-15';
        $lastUpdateString = '2023-05-20';

        $attributes = [
            'chemical' => 'Benzene',
            'type_of_toxicity' => 'cancer',
            'listing_mechanism' => 'Labor Code',
            'cas_no' => '71-43-2',
            'nsrl_or_madl' => '0.5 micrograms/day',
            'date_listed' => $dateListedString,
            'last_update' => $lastUpdateString,
        ];

        $chemical = Prop65ChemicalData::factory()->create($attributes);

        expect($chemical->chemical)->toBe('Benzene')
            ->and($chemical->type_of_toxicity)->toBe('cancer')
            ->and($chemical->listing_mechanism)->toBe('Labor Code')
            ->and($chemical->cas_no)->toBe('71-43-2')
            ->and($chemical->nsrl_or_madl)->toBe('0.5 micrograms/day')
            ->and($chemical->date_listed->format('Y-m-d'))->toBe($dateListedString)
            ->and($chemical->last_update->format('Y-m-d'))->toBe($lastUpdateString);
    });
});

describe('Prop65ChemicalData Scopes', function (): void {
    beforeEach(function (): void {
        // Create test data
        Prop65ChemicalData::factory()->create([
            'chemical' => 'Benzene',
            'type_of_toxicity' => 'cancer',
            'listing_mechanism' => 'Labor Code',
            'cas_no' => '71-43-2',
            'nsrl_or_madl' => '0.5 micrograms/day',
            'date_listed' => '2020-01-15',
        ]);

        Prop65ChemicalData::factory()->create([
            'chemical' => 'Lead',
            'type_of_toxicity' => 'reproductive toxicity',
            'listing_mechanism' => 'Authoritative Bodies',
            'cas_no' => '7439-92-1',
            'nsrl_or_madl' => null,
            'date_listed' => '2022-06-10',
        ]);

        Prop65ChemicalData::factory()->create([
            'chemical' => 'Formaldehyde',
            'type_of_toxicity' => 'cancer',
            'listing_mechanism' => 'Labor Code',
            'cas_no' => null,
            'nsrl_or_madl' => '1000 micrograms/day',
            'date_listed' => '2019-03-20',
        ]);
    });

    it('can scope by chemical name', function (): void {
        $benzeneChemicals = Prop65ChemicalData::byChemical('Benzene')->get();
        $leadChemicals = Prop65ChemicalData::byChemical('Lead')->get();

        expect($benzeneChemicals)->toHaveCount(1)
            ->and($leadChemicals)->toHaveCount(1);

        expect($benzeneChemicals->first()->chemical)->toBe('Benzene');
        expect($leadChemicals->first()->chemical)->toBe('Lead');
    });

    it('can scope by toxicity type', function (): void {
        $cancerChemicals = Prop65ChemicalData::byToxicityType('cancer')->get();
        $reproductiveChemicals = Prop65ChemicalData::byToxicityType('reproductive toxicity')->get();

        expect($cancerChemicals)->toHaveCount(2)
            ->and($reproductiveChemicals)->toHaveCount(1);

        $cancerChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->type_of_toxicity)->toBe('cancer');
        });

        $reproductiveChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->type_of_toxicity)->toBe('reproductive toxicity');
        });
    });

    it('can scope by listing mechanism', function (): void {
        $laborCodeChemicals = Prop65ChemicalData::byListingMechanism('Labor Code')->get();
        $authoritativeBodiesChemicals = Prop65ChemicalData::byListingMechanism('Authoritative Bodies')->get();

        expect($laborCodeChemicals)->toHaveCount(2)
            ->and($authoritativeBodiesChemicals)->toHaveCount(1);

        $laborCodeChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->listing_mechanism)->toBe('Labor Code');
        });

        $authoritativeBodiesChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->listing_mechanism)->toBe('Authoritative Bodies');
        });
    });

    it('can scope by CAS number', function (): void {
        $benzeneChemical = Prop65ChemicalData::byCasNumber('71-43-2')->get();
        $leadChemical = Prop65ChemicalData::byCasNumber('7439-92-1')->get();

        expect($benzeneChemical)->toHaveCount(1)
            ->and($leadChemical)->toHaveCount(1);

        expect($benzeneChemical->first()->cas_no)->toBe('71-43-2');
        expect($leadChemical->first()->cas_no)->toBe('7439-92-1');
    });

    it('can scope chemicals listed after a date', function (): void {
        $recentChemicals = Prop65ChemicalData::listedAfter('2020-01-01')->get();
        $veryRecentChemicals = Prop65ChemicalData::listedAfter('2022-01-01')->get();

        expect($recentChemicals)->toHaveCount(2)
            ->and($veryRecentChemicals)->toHaveCount(1);

        $recentChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->date_listed->format('Y-m-d'))->toBeGreaterThanOrEqual('2020-01-01');
        });
    });

    it('can scope chemicals listed before a date', function (): void {
        $olderChemicals = Prop65ChemicalData::listedBefore('2020-12-31')->get();
        $veryOldChemicals = Prop65ChemicalData::listedBefore('2019-12-31')->get();

        expect($olderChemicals)->toHaveCount(2)
            ->and($veryOldChemicals)->toHaveCount(1);

        $olderChemicals->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->date_listed->format('Y-m-d'))->toBeLessThanOrEqual('2020-12-31');
        });
    });

    it('can scope chemicals with NSRL or MADL values', function (): void {
        $chemicalsWithValues = Prop65ChemicalData::withNsrlOrMadl()->get();

        expect($chemicalsWithValues)->toHaveCount(2);

        $chemicalsWithValues->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->nsrl_or_madl)->not->toBeNull();
        });
    });

    it('can scope chemicals without NSRL or MADL values', function (): void {
        $chemicalsWithoutValues = Prop65ChemicalData::withoutNsrlOrMadl()->get();

        expect($chemicalsWithoutValues)->toHaveCount(1);

        $chemicalsWithoutValues->each(function (Prop65ChemicalData $chemical): void {
            expect($chemical->nsrl_or_madl)->toBeNull();
        });
    });

    it('can chain scopes', function (): void {
        $chainedChemicals = Prop65ChemicalData::byToxicityType('cancer')
            ->byListingMechanism('Labor Code')
            ->withNsrlOrMadl()
            ->listedAfter('2020-01-15')
            ->get();

        expect($chainedChemicals)->toHaveCount(1);

        $chemical = $chainedChemicals->first();
        expect($chemical->chemical)->toBe('Benzene')
            ->and($chemical->type_of_toxicity)->toBe('cancer')
            ->and($chemical->listing_mechanism)->toBe('Labor Code')
            ->and($chemical->nsrl_or_madl)->not->toBeNull();
    });
});

describe('Prop65ChemicalData Methods', function (): void {
    it('can check if chemical has CAS number', function (): void {
        $chemicalWithCas = Prop65ChemicalData::factory()->create([
            'cas_no' => '71-43-2',
        ]);

        $chemicalWithoutCas = Prop65ChemicalData::factory()->create([
            'cas_no' => null,
        ]);

        $chemicalWithEmptyCas = Prop65ChemicalData::factory()->create([
            'cas_no' => '',
        ]);

        expect($chemicalWithCas->hasCasNumber())->toBeTrue()
            ->and($chemicalWithoutCas->hasCasNumber())->toBeFalse()
            ->and($chemicalWithEmptyCas->hasCasNumber())->toBeFalse();
    });

    it('can check if chemical has toxicity type', function (): void {
        $chemicalWithToxicity = Prop65ChemicalData::factory()->create([
            'type_of_toxicity' => 'cancer',
        ]);

        $chemicalWithoutToxicity = Prop65ChemicalData::factory()->create([
            'type_of_toxicity' => null,
        ]);

        $chemicalWithEmptyToxicity = Prop65ChemicalData::factory()->create([
            'type_of_toxicity' => '',
        ]);

        expect($chemicalWithToxicity->hasToxicityType())->toBeTrue()
            ->and($chemicalWithoutToxicity->hasToxicityType())->toBeFalse()
            ->and($chemicalWithEmptyToxicity->hasToxicityType())->toBeFalse();
    });

    it('can check if chemical has listing mechanism', function (): void {
        $chemicalWithMechanism = Prop65ChemicalData::factory()->create([
            'listing_mechanism' => 'Labor Code',
        ]);

        $chemicalWithoutMechanism = Prop65ChemicalData::factory()->create([
            'listing_mechanism' => null,
        ]);

        $chemicalWithEmptyMechanism = Prop65ChemicalData::factory()->create([
            'listing_mechanism' => '',
        ]);

        expect($chemicalWithMechanism->hasListingMechanism())->toBeTrue()
            ->and($chemicalWithoutMechanism->hasListingMechanism())->toBeFalse()
            ->and($chemicalWithEmptyMechanism->hasListingMechanism())->toBeFalse();
    });

    it('can check if chemical has NSRL or MADL value', function (): void {
        $chemicalWithValue = Prop65ChemicalData::factory()->create([
            'nsrl_or_madl' => '0.5 micrograms/day',
        ]);

        $chemicalWithoutValue = Prop65ChemicalData::factory()->create([
            'nsrl_or_madl' => null,
        ]);

        $chemicalWithEmptyValue = Prop65ChemicalData::factory()->create([
            'nsrl_or_madl' => '',
        ]);

        expect($chemicalWithValue->hasNsrlOrMadl())->toBeTrue()
            ->and($chemicalWithoutValue->hasNsrlOrMadl())->toBeFalse()
            ->and($chemicalWithEmptyValue->hasNsrlOrMadl())->toBeFalse();
    });
});

describe('Prop65ChemicalData Datetime Casting', function (): void {
    it('casts date_listed to Carbon instance', function (): void {
        $chemical = Prop65ChemicalData::factory()->create([
            'date_listed' => '2020-01-15 10:30:00',
        ]);

        expect($chemical->date_listed)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($chemical->date_listed->format('Y-m-d'))->toBe('2020-01-15');
    });

    it('casts last_update to Carbon instance', function (): void {
        $chemical = Prop65ChemicalData::factory()->create([
            'last_update' => '2023-05-20 14:45:30',
        ]);

        expect($chemical->last_update)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($chemical->last_update->format('Y-m-d'))->toBe('2023-05-20');
    });

    it('handles different date formats', function (): void {
        $chemical = Prop65ChemicalData::factory()->create([
            'date_listed' => '2020-12-25',
            'last_update' => '2023-01-01 00:00:00',
        ]);

        expect($chemical->date_listed->year)->toBe(2020)
            ->and($chemical->date_listed->month)->toBe(12)
            ->and($chemical->date_listed->day)->toBe(25)
            ->and($chemical->last_update->year)->toBe(2023)
            ->and($chemical->last_update->month)->toBe(1)
            ->and($chemical->last_update->day)->toBe(1);
    });
});

describe('Prop65ChemicalData Factory States', function (): void {
    it('can create chemical with CAS number using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->withCasNumber()->create();

        expect($chemical->hasCasNumber())->toBeTrue()
            ->and($chemical->cas_no)->toMatch('/^\d{2,7}-\d{2}-\d$/');
    });

    it('can create chemical without CAS number using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->withoutCasNumber()->create();

        expect($chemical->hasCasNumber())->toBeFalse()
            ->and($chemical->cas_no)->toBeNull();
    });

    it('can create chemical with NSRL or MADL using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->withNsrlOrMadl()->create();

        expect($chemical->hasNsrlOrMadl())->toBeTrue()
            ->and($chemical->nsrl_or_madl)->not->toBeNull();
    });

    it('can create chemical without NSRL or MADL using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->withoutNsrlOrMadl()->create();

        expect($chemical->hasNsrlOrMadl())->toBeFalse()
            ->and($chemical->nsrl_or_madl)->toBeNull();
    });

    it('can create cancer-causing chemical using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->cancer()->create();

        expect($chemical->type_of_toxicity)->toBe('cancer');
    });

    it('can create reproductive toxicity chemical using factory state', function (): void {
        $chemical = Prop65ChemicalData::factory()->reproductiveToxicity()->create();

        expect($chemical->type_of_toxicity)->toBe('reproductive toxicity');
    });

    it('can chain factory states', function (): void {
        $chemical = Prop65ChemicalData::factory()
            ->cancer()
            ->withCasNumber()
            ->withNsrlOrMadl()
            ->create();

        expect($chemical->type_of_toxicity)->toBe('cancer')
            ->and($chemical->hasCasNumber())->toBeTrue()
            ->and($chemical->hasNsrlOrMadl())->toBeTrue();
    });
});

describe('Prop65ChemicalData Validation', function (): void {
    it('requires chemical name', function (): void {
        expect(function (): void {
            Prop65ChemicalData::factory()->create([
                'chemical' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires date_listed', function (): void {
        expect(function (): void {
            Prop65ChemicalData::factory()->create([
                'date_listed' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('requires last_update', function (): void {
        expect(function (): void {
            Prop65ChemicalData::factory()->create([
                'last_update' => null,
            ]);
        })->toThrow(Exception::class);
    });

    it('allows null values for optional fields', function (): void {
        $chemical = Prop65ChemicalData::factory()->create([
            'type_of_toxicity' => null,
            'listing_mechanism' => null,
            'cas_no' => null,
            'nsrl_or_madl' => null,
        ]);

        expect($chemical->type_of_toxicity)->toBeNull()
            ->and($chemical->listing_mechanism)->toBeNull()
            ->and($chemical->cas_no)->toBeNull()
            ->and($chemical->nsrl_or_madl)->toBeNull();
    });
});
