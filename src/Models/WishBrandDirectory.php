<?php

namespace IGE\ChannelLister\Models;

use IGE\ChannelLister\Database\Factories\WishBrandDirectoryFactory;
use IGE\ChannelLister\Models\Concerns\HasConfigurableConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $brand_id
 * @property string $brand_name
 * @property string|null $brand_website_url
 * @property \Carbon\Carbon $last_update
 */
class WishBrandDirectory extends Model
{
    use HasConfigurableConnection;

    /** @use HasFactory<WishBrandDirectoryFactory> */
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WishBrandDirectoryFactory
    {
        return WishBrandDirectoryFactory::new();
    }

    /**
     * Scope to search brands by name using FULLTEXT search
     *
     * @param  Builder<WishBrandDirectory>  $query
     * @param  string  $searchTerm
     */
    public function scopeSearchByName(Builder $query, $searchTerm): void
    {
        $query->whereRaw('MATCH(brand_name) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchTerm]);
    }

    /**
     * Scope to search brands by name using FULLTEXT search in boolean mode
     *
     * @param  Builder<WishBrandDirectory>  $query
     * @param  string  $searchTerm
     */
    public function scopeSearchByNameBoolean(Builder $query, $searchTerm): void
    {
        $query->whereRaw('MATCH(brand_name) AGAINST(? IN BOOLEAN MODE)', [$searchTerm]);
    }

    /**
     * Scope to filter by brand ID
     *
     * @param  Builder<WishBrandDirectory>  $query
     * @param  string  $brandId
     */
    public function scopeByBrandId(Builder $query, $brandId): void
    {
        $query->where('brand_id', strtoupper($brandId));
    }

    /**
     * Scope to filter brands with websites
     *
     * @param  Builder<WishBrandDirectory>  $query
     */
    public function scopeWithWebsite($query): void
    {
        $query->whereNotNull('brand_website_url');
    }

    /**
     * Scope to filter brands without websites
     *
     * @param  Builder<WishBrandDirectory>  $query
     */
    public function scopeWithoutWebsite($query): void
    {
        $query->whereNull('brand_website_url');
    }

    /**
     * Scope to filter brands updated after a certain date
     *
     * @param  Builder<WishBrandDirectory>  $query
     * @param  string  $date
     */
    public function scopeUpdatedAfter(Builder $query, $date): void
    {
        $query->where('last_update', '>=', $date);
    }

    /**
     * Scope to filter brands updated before a certain date
     *
     * @param  Builder<WishBrandDirectory>  $query
     * @param  string  $date
     */
    public function scopeUpdatedBefore(Builder $query, $date): void
    {
        $query->where('last_update', '<=', $date);
    }

    /**
     * Scope to filter brands by domain
     *
     * @param  Builder<WishBrandDirectory>  $query
     */
    public function scopeByDomain(Builder $query, string $domain): void
    {
        $query->where('brand_website_url', 'LIKE', '%'.$domain.'%');
    }

    /**
     * Scope to filter secure URLs only
     *
     * @param  Builder<WishBrandDirectory>  $query
     */
    public function scopeSecureUrls($query): void
    {
        $query->where('brand_website_url', 'LIKE', 'https://%');
    }

    /**
     * Check if brand has a website URL
     */
    public function hasWebsite(): bool
    {
        return ! empty($this->brand_website_url);
    }

    /**
     * Get the brand's slug (URL-friendly version of name)
     *
     * @return string|null
     */
    public function getSlug()
    {
        if (! $this->brand_name) {
            return null;
        }

        // Replace & with -and- and + with plus, then handle spaces
        $slug = str_replace(['&', '+'], ['-and-', 'plus'], $this->brand_name);

        // Replace spaces with hyphens
        $slug = str_replace(' ', '-', $slug);

        // Clean up multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim((string) $slug, '-');

        return strtolower($slug);
    }

    /**
     * Brand Name Accessor - Always return title case
     */
    protected function displayBrandName(): Attribute
    {
        return Attribute::make(
            get: fn (null $value): string => str($this->brand_name)->title()->toString(),
        );
    }

    /**
     * Brand Website URL Accessor/Mutator - Ensure proper URL format
     */
    protected function brandWebsiteUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? strtolower((string) $value) : null,
            set: function ($value) {
                if (! $value) {
                    return null;
                }

                $value = trim($value);

                // Add https:// if no protocol is specified
                if (in_array(preg_match('/^https?:\/\//i', $value), [0, false])) {
                    $value = "https://{$value}";
                }

                // Parse the URL to validate and normalize it
                $parsed = parse_url($value);

                // If parsing failed, return null
                if ($parsed === false || ! isset($parsed['host'])) {
                    return null;
                }

                // Force HTTPS for security
                $scheme = 'https';
                $host = $parsed['host'];
                $path = $parsed['path'] ?? '';
                $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

                return strtolower($scheme.'://'.$host.$path.$query.$fragment);
            }
        );
    }

    /**
     * Virtual attribute to get domain from URL
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->brand_website_url) {
                    return null;
                }

                $parsed = parse_url($this->brand_website_url);

                return $parsed['host'] ?? null;
            }
        );
    }
}
