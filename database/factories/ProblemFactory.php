<?php

namespace aclai\piton\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use aclai\piton\Problem;

class ProblemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Problem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'inputTables' => [$this->faker->word],
            'inputColumns' => [$this->faker->word],
            'outputColumns' => [$this->faker->word],
            'whereClauses' => [],
            'OrderByClauses' => [],
            'limit' => null,
            'identifierColumnName' => $this->faker->word,
        ];
    }
}
