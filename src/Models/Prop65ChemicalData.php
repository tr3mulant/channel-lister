<?php

namespace IGE\ChannelLister\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Prop65ChemicalData extends Model
{
    use HasFactory;

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
     * Indicates if the model should be timestamped.
     * We're using a custom timestamp column, so disable default timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date_listed',
        'last_update',
    ];

    /**
     * Scope to filter by chemical name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $chemical
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChemical($query, $chemical)
    {
        return $query->where('chemical', $chemical);
    }

    /**
     * Scope to filter by type of toxicity.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByToxicityType($query, $type)
    {
        return $query->where('type_of_toxicity', $type);
    }

    /**
     * Scope to filter by listing mechanism.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $mechanism
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByListingMechanism($query, $mechanism)
    {
        return $query->where('listing_mechanism', $mechanism);
    }

    /**
     * Scope to filter by CAS number.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $casNo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCasNumber($query, $casNo)
    {
        return $query->where('cas_no', $casNo);
    }

    /**
     * Scope to filter chemicals listed after a certain date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeListedAfter($query, $date)
    {
        return $query->where('date_listed', '>=', $date);
    }

    /**
     * Scope to filter chemicals listed before a certain date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeListedBefore($query, $date)
    {
        return $query->where('date_listed', '<=', $date);
    }

    /**
     * Scope to filter chemicals that have NSRL or MADL values.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithNsrlOrMadl($query)
    {
        return $query->whereNotNull('nsrl_or_madl');
    }

    /**
     * Scope to filter chemicals that don't have NSRL or MADL values.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutNsrlOrMadl($query)
    {
        return $query->whereNull('nsrl_or_madl');
    }

    /**
     * Check if the chemical has a CAS number.
     *
     * @return bool
     */
    public function hasCasNumber()
    {
        return !empty($this->cas_no);
    }

    /**
     * Check if the chemical has toxicity type information.
     *
     * @return bool
     */
    public function hasToxicityType()
    {
        return !empty($this->type_of_toxicity);
    }

    /**
     * Check if the chemical has listing mechanism information.
     *
     * @return bool
     */
    public function hasListingMechanism()
    {
        return !empty($this->listing_mechanism);
    }

    /**
     * Check if the chemical has NSRL or MADL value.
     *
     * @return bool
     */
    public function hasNsrlOrMadl()
    {
        return !empty($this->nsrl_or_madl);
    }
}