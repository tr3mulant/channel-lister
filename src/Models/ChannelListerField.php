<?php

namespace IGE\ChannelLister\Models;

use IGE\ChannelLister\Database\Factories\ChannelListerFieldFactory;
use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $ordering
 * @property string $field_name
 * @property string|null $display_name
 * @property string|null $tooltip
 * @property string|null $example
 * @property string $marketplace
 * @property InputType $input_type
 * @property string|null $input_type_aux
 * @property bool $required
 * @property string $grouping
 * @property Type $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ChannelListerField extends Model
{
    /** @use HasFactory<ChannelListerFieldFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_fields';

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
        'type' => Type::class,
    ];

    /**
     * Get the table columns for the Channel Lister Field index view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTableColumns(): array
    {
        return [
            ['label' => 'Order', 'value' => 'ordering'],
            ['label' => 'Field Name', 'value' => 'field_name'],
            ['label' => 'Display Name', 'value' => 'display_name'],
            [
                'label' => 'Marketplace',
                'value' => fn ($field): string => '<span class=\"badge badge-secondary\">'.$field->marketplace.'</span>',
            ],
            ['label' => 'Input Type', 'value' => fn ($field) => $field->input_type->value],
            [
                'label' => 'Required',
                'value' => fn ($field): string => $field->required
                    ? '<span class=\"badge badge-danger\">Required</span>'
                    : '<span class=\"badge badge-secondary\">Optional</span>',
            ],
            ['label' => 'Grouping', 'value' => 'grouping'],
            ['label' => 'Type', 'value' => fn ($field) => $field->type->value],
            ['label' => 'Tool Tip', 'value' => 'tooltip'],
            [
                'label' => 'Actions',
                'value' => fn ($field): string => '
                                <div class=\"btn-group\" role=\"group\">
                                    <a href=\"'.route('channel-lister-field.show', $field->id).'\" class=\"btn btn-sm btn-outline-info\">View</a>
                                    <a href=\"'.route('channel-lister-field.edit', $field->id).'\" class=\"btn btn-sm btn-outline-primary\">Edit</a>
                                    <form action=\"'.route('channel-lister-field.destroy', $field->id).'\" method=\"POST\" style=\"display: inline;\">
                                        '.csrf_field().method_field('DELETE').'
                                        <button type=\"submit\" class=\"btn btn-sm btn-outline-danger\" onclick=\"return confirm(\'Are you sure you want to delete this field?\')\">Delete</button>
                                    </form>
                                </div>
                            ',
            ],
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChannelListerFieldFactory
    {
        return ChannelListerFieldFactory::new();
    }

    /**
     * Scope a query to only include fields for a specific marketplace.
     *
     * @param  Builder<ChannelListerField>  $query
     */
    public function scopeForMarketplace(Builder $query, string $marketplace): void
    {
        $query->where('marketplace', $marketplace);
    }

    /**
     * Scope a query to only include required fields.
     *
     * @param  Builder<ChannelListerField>  $query
     */
    public function scopeRequired(Builder $query): void
    {
        $query->where('required', true);
    }

    /**
     * Scope a query to only include fields by grouping.
     *
     * @param  Builder<ChannelListerField>  $query
     */
    public function scopeByGrouping(Builder $query, string $grouping): void
    {
        $query->where('grouping', $grouping);
    }

    /**
     * Scope a query to order by the ordering field.
     *
     * @param  Builder<ChannelListerField>  $query
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy('ordering', $direction);
    }

    /**
     * Check if this field is a custom field.
     */
    public function isCustom(): bool
    {
        return $this->type === Type::CUSTOM;
    }

    /**
     * Check if this field is a ChannelAdvisor field.
     */
    public function isChannelAdvisor(): bool
    {
        return $this->type === Type::CHANNEL_ADVISOR;
    }

    /**
     * Return the input type auxiliary options.
     *
     * @return null|string|array<int,string>
     */
    public function getInputTypeAuxOptions(): null|string|array
    {
        $input_type_aux = $this->input_type_aux;

        if (! $input_type_aux) {
            return $input_type_aux;
        }

        if (str_contains($input_type_aux, '||')) {
            return explode('||', $input_type_aux);
        }

        return $input_type_aux;
    }

    // public function render(): Htmlable
    // {
    //     if (! isset($params['type'])) {
    //         throw new \RuntimeException("Params missing required field 'type'");
    //     }
    //     try {
    //         $html = match ($params['input_type']) {
    //             'alert' => $this->buildAlertMessage($params),
    //             'checkbox' => $this->buildCheckboxFormInput($params),
    //             'clonesite-tags' => $this->buildCloneSiteTagsHtml($params),
    //             'clonesite-cats' => $this->buildCloneSiteCategoryHtml($params),
    //             'commaseparated' => $this->buildCommaSeparatedFormInput($params),
    //             'currency' => $this->buildCurrencyFormInput($params),
    //             'custom' => $this->buildCustomFormInput($params),
    //             'decimal' => $this->buildDecimalFormInput($params),
    //             'integer' => $this->buildIntegerFormInput($params),
    //             'select' => $this->buildSelectFormInput($params),
    //             'text' => $this->buildTextFormInput($params),
    //             'textarea' => $this->buildTextareaFormInput($params),
    //             'url' => $this->buildUrlFormInput($params),
    //             default => throw new \RuntimeException("Unrecognized input_type: '{$params['input_type']}'"),
    //         };
    //     } catch (\Exception $e) {
    //         $html = $this->exceptionToAlert($e);
    //     }

    //     return $html;
    // }

    /**
     * Get the parsed input_type_aux as an array. And
     * Set the input_type_aux from an array.
     */
    protected function inputTypeAux(): Attribute
    {
        return Attribute::make(
            // get: function (?string $value): null|string|array {
            //     if ($value === null || $value === '' || $value === '0') {
            //         return null;
            //     }

            //     if (str_contains($value, '||')) {
            //         return explode('||', $value);
            //     }

            //     return $value;
            // },
            set: fn (null|string|array $value): ?string => is_array($value) ? implode('||', $value) : $value
        );
    }

    /**
     * Get the display name or fall back to field name.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes): string {
                if ($value !== null && $value !== '' && $value !== '0') {
                    return $value;
                }

                return Str::of($attributes['field_name'])->replace('_', ' ')->title()->toString();
            }
        );
    }
}
