<?php

namespace IGE\ChannelLister\Models;

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * This is placeholder for m/ChannelLister.
 * I don't expect to keep this as we will need to extract the database records the m/ChannelLister
 * creates/uses into their own model classes
 */
class ChannelListerField extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_fields';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    //public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ordering',
        'field_name',
        'display_name',
        'tooltip',
        'example',
        'marketplace',
        'input_type',
        'input_type_aux',
        'required',
        'grouping',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required' => 'boolean',
        'ordering' => 'integer',
        'input_type' => InputType::class,
        'type' => Type::class
    ];

    /**
     * Get the parsed input_type_aux as an array. And 
     * Set the input_type_aux from an array.
     *
     * @return Attribute
     */
    protected function inputTypeAux(): Attribute {
        
        return Attribute::make(
            get: function (string $value) {
                if (empty($value)) {
                    return [];
                }
        
                return explode('||', $value);
            },
            set: function (array $value) {
                return implode('||', $value);
            }
        );
    }

    /**
     * Get the display name or fall back to field name.
     *
     * @return Attribute
     */
    protected function displayName(): Attribute
    {
        //return $this->attributes['display_name'] ?? $this->field_name;

        return Attribute::make(
            get: function (?string $value): string {
               if($value){
                return $value; 
               } 
               return str($this->field_name)->replace('_', ' ')->title()->toString();
            }
        );
    }

    /**
     * Scope a query to only include fields for a specific marketplace.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $marketplace
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMarketplace($query, string $marketplace)
    {
        return $query->where('marketplace', $marketplace);
    }

    /**
     * Scope a query to only include required fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    /**
     * Scope a query to only include fields by grouping.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $grouping
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGrouping($query, string $grouping)
    {
        return $query->where('grouping', $grouping);
    }

    /**
     * Scope a query to order by the ordering field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('ordering', $direction);
    }

    /**
     * Check if this field is a custom field.
     *
     * @return bool
     */
    public function isCustom(): bool
    {
        return $this->type === Type::CUSTOM;
    }

    /**
     * Check if this field is a ChannelAdvisor field.
     *
     * @return bool
     */
    public function isChannelAdvisor(): bool
    {
        return $this->type === Type::CHANNEL_ADVISOR;
    }

}
