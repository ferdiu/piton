<?php

namespace aclai\piton\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\ClassModel;

class SaveClassModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_class_model_can_be_created_with_the_factory()
    {
        ClassModel::factory()->create();

        $this->assertCount(1, ClassModel::all());
    }
}