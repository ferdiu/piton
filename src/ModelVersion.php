<?php

namespace aclai\piton;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    use HasFactory;

    /**
     * @var array
     */
	protected $guarded = [];

	protected $connection = "piton_connection";

	protected $table = "piton_model_version";

    /**
     * It casts the columns from JSON to an array automatically without need for a json_decode().
     */
    protected function casts(): array
    {
        return [
            'hierarchy' => 'array',
            'allData' => 'array',
            'trainData' => 'array',
            'testData' => 'array',
        ];
    }

    /**
     * Resolve the factory instance for this model.
     */
    protected static function newFactory(): \aclai\piton\Database\Factories\ModelVersionFactory
    {
        return \aclai\piton\Database\Factories\ModelVersionFactory::new();
    }
}