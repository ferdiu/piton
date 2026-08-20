<?php

namespace aclai\piton\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\ClassModel;
use aclai\piton\DiscriminativeModels\RuleBasedModel;

class CreateModelFromDBTest extends TestCase
{
    public function test_a_model_can_be_created_from_db_specifying_its_id()
    {
      $classModel = ClassModel::orderByDesc('id')->first(); # most recent model created
      if ($classModel === null) {
          $this->markTestSkipped('No trained ClassModel exists in the database.');
      }
      $model = RuleBasedModel::createFromDB($classModel->id);
      echo "Model created: " . $model;
      $this->assertTrue(true);
    }

    public function test_a_class_model_can_be_created_from_db_no_learner_specified()
    {
        $classModel = ClassModel::orderByDesc('id')->first();
        if ($classModel === null) {
            $this->markTestSkipped('No trained ClassModel exists in the database.');
        }
        $model = RuleBasedModel::createFromDB($classModel->id);
        echo "Model created: " . $model;
        $this->assertTrue(true);
    }

    public function test_a_class_model_can_be_created_from_db_specifying_a_learner()
    {
        $classModel = ClassModel::orderByDesc('id')->first();
        if ($classModel === null) {
            $this->markTestSkipped('No trained ClassModel exists in the database.');
        }
        $model = RuleBasedModel::createFromDB($classModel->id, 'PRip');
        echo "Model created: " . $model;
        $this->assertTrue(true);
    }
}