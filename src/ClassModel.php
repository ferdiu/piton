<?php

namespace aclai\piton;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    /**
     * @var array
     */
	protected $guarded = [];

	protected $connection = "piton_connection";

	protected $table = "piton_class_model";

    /**
     * It casts the column rules from JSON to an array automatically.
     * This way, I will receive $classModel->rules as array and don’t need to do json_decode().
     */
    protected function casts(): array
    {
        return [
            'class' => 'array',
            'rules' => 'array',
            'json_logic_rules' => 'array',
            'attributes' => 'array'
        ];
    }

    /**
     * Resolve the factory instance for this model.
     */
    protected static function newFactory(): \aclai\piton\Database\Factories\ClassModelFactory
    {
        return \aclai\piton\Database\Factories\ClassModelFactory::new();
    }
}