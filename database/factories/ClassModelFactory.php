<?php

namespace aclai\piton\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use aclai\piton\ClassModel;
use aclai\piton\ModelVersion;

class ClassModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ClassModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_model_version' => ModelVersion::factory(),
            'class' => [$this->faker->word],
            'rules' => [$this->faker->sentence],
            'json_logic_rules' => [$this->faker->sentence],
            'attributes' => [$this->faker->word],
            'totNumRules' => $this->faker->numberBetween(1, 100),
            'test_date' => $this->faker->dateTime(),
        ];
    }
}
