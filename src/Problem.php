<?php

namespace aclai\piton;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    /**
     * @var array
     */
	protected $guarded = [];

	protected $connection = "piton_connection";

	protected $table = "piton_problems";

    /**
     * It casts the columns from JSON to an array automatically without need for a json_decode().
     */
    protected function casts(): array
    {
        return [
            'inputTables' => 'array',
            'inputColumns' => 'array',
            'outputColumns' => 'array',
            'whereClauses' => 'array',
            'OrderByClauses' => 'array',
        ];
    }

    /**
     * Resolve the factory instance for this model.
     */
    protected static function newFactory(): \aclai\piton\Database\Factories\ProblemFactory
    {
        return \aclai\piton\Database\Factories\ProblemFactory::new();
    }
}