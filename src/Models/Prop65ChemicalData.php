<?php

namespace IGE\ChannelLister\Models;

use IGE\ChannelLister\Database\Factories\Prop65ChemicalDataFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $chemical
 * @property string|null $type_of_toxicity
 * @property string|null $listing_mechanism
 * @property string|null $cas_no
 * @property string|null $nsrl_or_madl
 * @property \Carbon\Carbon $date_listed
 * @property \Carbon\Carbon $last_update
 */
class Prop65ChemicalData extends Model
{
    /** @use HasFactory<Prop65ChemicalDataFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * We're using a custom timestamp column, so disable default timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_prop65_chemical_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chemical',
        'type_of_toxicity',
        'listing_mechanism',
        'cas_no',
        'date_listed',
        'nsrl_or_madl',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_listed' => 'datetime',
        'last_update' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Prop65ChemicalDataFactory
    {
        return Prop65ChemicalDataFactory::new();
    }

    /**
     * Scope to filter by chemical name.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $chemical
     */
    public function scopeByChemical(Builder $query, $chemical): void
    {
        $query->where('chemical', $chemical);
    }

    /**
     * Scope to filter by type of toxicity.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $type
     */
    public function scopeByToxicityType(Builder $query, $type): void
    {
        $query->where('type_of_toxicity', $type);
    }

    /**
     * Scope to filter by listing mechanism.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $mechanism
     */
    public function scopeByListingMechanism(Builder $query, $mechanism): void
    {
        $query->where('listing_mechanism', $mechanism);
    }

    /**
     * Scope to filter by CAS number.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $casNo
     */
    public function scopeByCasNumber(Builder $query, $casNo): void
    {
        $query->where('cas_no', $casNo);
    }

    /**
     * Scope to filter chemicals listed after a certain date.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $date
     */
    public function scopeListedAfter(Builder $query, $date): void
    {
        $query->where('date_listed', '>=', $date);
    }

    /**
     * Scope to filter chemicals listed before a certain date.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     * @param  string  $date
     */
    public function scopeListedBefore(Builder $query, $date): void
    {
        $query->where('date_listed', '<=', $date);
    }

    /**
     * Scope to filter chemicals that have NSRL or MADL values.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     */
    public function scopeWithNsrlOrMadl(Builder $query): void
    {
        $query->whereNotNull('nsrl_or_madl');
    }

    /**
     * Scope to filter chemicals that don't have NSRL or MADL values.
     *
     * @param  Builder<Prop65ChemicalData>  $query
     */
    public function scopeWithoutNsrlOrMadl(Builder $query): void
    {
        $query->whereNull('nsrl_or_madl');
    }

    /**
     * Check if the chemical has a CAS number.
     */
    public function hasCasNumber(): bool
    {
        return ! empty($this->cas_no);
    }

    /**
     * Check if the chemical has toxicity type information.
     */
    public function hasToxicityType(): bool
    {
        return ! empty($this->type_of_toxicity);
    }

    /**
     * Check if the chemical has listing mechanism information.
     */
    public function hasListingMechanism(): bool
    {
        return ! empty($this->listing_mechanism);
    }

    /**
     * Check if the chemical has NSRL or MADL value.
     */
    public function hasNsrlOrMadl(): bool
    {
        return ! empty($this->nsrl_or_madl);
    }
}
