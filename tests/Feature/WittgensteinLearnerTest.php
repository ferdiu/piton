<?php

namespace aclai\piton\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\ClassModel;
use aclai\piton\Instances\Instances;
use aclai\piton\Learners\WittgensteinLearner;

class WittgensteinLearnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->pythonPackageAvailable('wittgenstein')) {
            $this->markTestSkipped('The wittgenstein Python package is not available.');
        }
    }

    /**
     * Determine whether a Python package can be imported.
     */
    private function pythonPackageAvailable(string $package): bool
    {
        exec('python3 -c ' . escapeshellarg('import ' . $package) . ' 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    public function test_a_model_can_be_created_from_an_object_of_type_instances_with_RIPPERk()
    {
        $trainData = Instances::createFromARFF(__DIR__."/../Arff/iris.arff");
        $learner = new WittgensteinLearner("RIPPERk", 2);
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        echo "MODEL:" . PHP_EOL . $model . PHP_EOL;
        $this->assertTrue(true);
    }

    public function test_a_model_can_be_created_from_an_object_of_type_instances_with_IREP()
    {
        $trainData = Instances::createFromARFF(__DIR__."/../Arff/iris.arff");
        $learner = new WittgensteinLearner("IREP", 2);
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        echo "MODEL:" . PHP_EOL . $model . PHP_EOL;
        $this->assertTrue(true);
    }

    public function test_a_model_can_be_stored_into_the_database()
    {
        $trainData = Instances::createFromARFF(__DIR__."/../Arff/iris.arff");
        $learner = new WittgensteinLearner("IREP", 2);
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        $model->saveToDB(1, "myIris", "Wittgenstein IREP");
        $this->assertCount(1, ClassModel::all());
    }

    public function test_evaluation_of_a_model_before_storing_it_into_the_database()
    {
        $trainData = Instances::createFromARFF(__DIR__."/../Arff/iris.arff");
        $testData = Instances::createFromARFF(__DIR__."/../Arff/irisTest.arff");
        $learner = new WittgensteinLearner("IREP", 2);
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        $model->saveToDB(1, "myIris", "Wittgenstein IREP", $testData);
        $this->assertCount(1, ClassModel::all());
    }
}