<?php

namespace IGE\ChannelLister\Database\Factories;

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelListerField>
 */
class ChannelListerFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ChannelListerField>
     */
    protected $model = ChannelListerField::class;

    /**
     * {@inheritdoc}
     */
    public function definition()
    {
        return [
            'ordering' => $this->faker->randomNumber(),
            'field_name' => $this->faker->unique()->word(),
            'display_name' => $this->faker->word(),
            'tooltip' => $this->faker->sentence(),
            'example' => $this->faker->word(),
            'marketplace' => $this->faker->word(),
            'input_type' => $this->faker->randomElement(InputType::cases()),
            'input_type_aux' => $this->faker->randomElement(['success', 'info', 'warning', 'danger']),
            'required' => $this->faker->boolean(),
            'grouping' => $this->faker->word(),
            'type' => $this->faker->randomElement(Type::cases()),
        ];
    }
}
