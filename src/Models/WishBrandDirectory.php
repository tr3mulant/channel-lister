<?php

namespace IGE\ChannelLister\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class WishBrandDirectory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_wish_brand_directory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand_id',
        'brand_name',
        'brand_website_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
        'last_update',
    ];

    /**
     * Brand Name Accessor - Always return title case
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (null $value): string => str($this->field_name)->replace('_', ' ')->title()->toString(),
        );
    }

    /**
     * Brand Website URL Accessor/Mutator - Ensure proper URL format
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function brandWebsiteUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? strtolower($value) : null,
            set: function ($value) {
                if (!$value) return null;
                
                $value = trim($value);
                // Add https:// if no protocol is specified
                if (!preg_match('/^https?:\/\//', $value)) {
                    $value = "https://{$value}";
                }
                return strtolower($value);
            }
        );
    }

    /**
     * Virtual attribute to get domain from URL
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->brand_website_url) return null;
                
                $parsed = parse_url($this->brand_website_url);
                return $parsed['host'] ?? null;
            }
        );
    }

    /**
     * Scope to search brands by name using FULLTEXT search
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByName($query, $searchTerm)
    {
        return $query->whereRaw('MATCH(brand_name) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchTerm]);
    }

    /**
     * Scope to search brands by name using FULLTEXT search in boolean mode
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByNameBoolean($query, $searchTerm)
    {
        return $query->whereRaw('MATCH(brand_name) AGAINST(? IN BOOLEAN MODE)', [$searchTerm]);
    }

    /**
     * Scope to filter by brand ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $brandId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBrandId($query, $brandId)
    {
        return $query->where('brand_id', strtoupper($brandId));
    }

    /**
     * Scope to filter brands with websites
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithWebsite($query)
    {
        return $query->whereNotNull('brand_website_url');
    }

    /**
     * Scope to filter brands without websites
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutWebsite($query)
    {
        return $query->whereNull('brand_website_url');
    }

    /**
     * Scope to filter brands updated after a certain date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpdatedAfter($query, $date)
    {
        return $query->where('last_update', '>=', $date);
    }

    /**
     * Scope to filter brands updated before a certain date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpdatedBefore($query, $date)
    {
        return $query->where('last_update', '<=', $date);
    }

    /**
     * Scope to filter brands by domain
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $domain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDomain($query, $domain)
    {
        return $query->where('brand_website_url', 'LIKE', '%' . $domain . '%');
    }

    /**
     * Scope to filter secure URLs only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSecureUrls($query)
    {
        return $query->where('brand_website_url', 'LIKE', 'https://%');
    }

    /**
     * Check if brand has a website URL
     *
     * @return bool
     */
    public function hasWebsite()
    {
        return !empty($this->brand_website_url);
    }

    /**
     * Get the brand's slug (URL-friendly version of name)
     *
     * @return string|null
     */
    public function getSlug()
    {
        if (!$this->brand_name) return null;
        
        return strtolower(str_replace([' ', '&', '+'], ['-', 'and', 'plus'], $this->brand_name));
    }
}