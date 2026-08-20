<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\Tests\TestCase;
use aclai\piton\ModelVersion;

class SaveModelVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_model_version_can_be_created_with_the_factory()
    {
        ModelVersion::factory()->create();

        $this->assertCount(1, ModelVersion::all());
    }
}