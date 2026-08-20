<?php

namespace aclai\piton\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use aclai\piton\ModelVersion;
use aclai\piton\Problem;

class ModelVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ModelVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_problem' => Problem::factory(),
            'id_author' => $this->faker->numberBetween(1, 100),
            'learner' => $this->faker->word,
            'training_mode' => 'FullTraining',
            'cut_off_value' => $this->faker->randomFloat(2, 0, 1),
            'experiment_id' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->dateTime(),
            'hierarchy' => [$this->faker->word],
            'test_results' => $this->faker->paragraph,
            'test_date' => $this->faker->dateTime(),
        ];
    }
}
