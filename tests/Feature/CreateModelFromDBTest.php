<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\Tests\TestCase;
use aclai\piton\ClassModel;
use aclai\piton\DiscriminativeModels\RuleBasedModel;
use aclai\piton\Instances\Instances;
use aclai\piton\Learners\PRip;
use aclai\piton\ModelVersion;

class CreateModelFromDBTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Train a small PRip model from the iris ARFF file and persist it to the database.
     */
    private function seedRuleBasedModel(): ClassModel
    {
        $modelVersion = ModelVersion::factory()->create();
        $trainData = Instances::createFromARFF(__DIR__ . "/../Arff/iris.arff");
        $learner = new PRip();
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        $classModelId = $model->saveToDB($modelVersion->id);

        return ClassModel::findOrFail($classModelId);
    }

    public function test_a_model_can_be_created_from_db_specifying_its_id()
    {
        $classModel = $this->seedRuleBasedModel();
        $model = RuleBasedModel::createFromDB($classModel->id);
        echo "Model created: " . $model;
        $this->assertInstanceOf(RuleBasedModel::class, $model);
        $this->assertNotEmpty($model->getRules());
        $this->assertNotEmpty($model->getAttributes());
    }

    public function test_a_class_model_can_be_created_from_db_no_learner_specified()
    {
        $classModel = $this->seedRuleBasedModel();
        $model = RuleBasedModel::createFromDB($classModel->id);
        echo "Model created: " . $model;
        $this->assertInstanceOf(RuleBasedModel::class, $model);
        $this->assertNotEmpty($model->getRules());
        $this->assertNotEmpty($model->getAttributes());
    }

    public function test_a_class_model_can_be_created_from_db_specifying_a_learner()
    {
        $classModel = $this->seedRuleBasedModel();
        $model = RuleBasedModel::createFromDB($classModel->id, 'PRip');
        echo "Model created: " . $model;
        $this->assertInstanceOf(RuleBasedModel::class, $model);
        $this->assertNotEmpty($model->getRules());
        $this->assertNotEmpty($model->getAttributes());
    }
}