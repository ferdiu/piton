<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\Tests\TestCase;
use aclai\piton\ClassModel;
use aclai\piton\DBFit\DBFit;
use aclai\piton\DiscriminativeModels\RuleBasedModel;
use aclai\piton\Instances\Instances;
use aclai\piton\Learners\PRip;
use aclai\piton\ModelVersion;
use aclai\piton\PitonBaseServiceProvider;

class PredictionTest extends TestCase
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

    public function test_a_prediction_on_an_instance_of_given_id_can_be_done()
    {
        $classModel = $this->seedRuleBasedModel();

        if (config('piton.outputColumns') === null) {
            $this->markTestSkipped('A published piton config with outputColumns and a matching database schema is required.');
        }

        $db_fit = new DBFit();
        $model = RuleBasedModel::createFromDB($classModel->id);
        echo "Model created: " . $model;
        $db_fit->setIdentifierColumnName('referti.id');
        $db_fit->setOutputColumns(config('piton.outputColumns'));
        $results = $db_fit->predictByIdentifier(1);
        $this->assertIsArray($results);
        $this->assertIsArray($db_fit->getPredictionResults());
    }
}