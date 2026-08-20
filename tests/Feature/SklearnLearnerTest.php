<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\Tests\TestCase;
use aclai\piton\Instances\Instances;
use aclai\piton\Learners\SklearnLearner;

class SklearnLearnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->pythonPackageAvailable('sklearn')) {
            $this->markTestSkipped('The sklearn Python package is not available.');
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

    public function test_a_model_can_be_created_from_an_object_of_type_instances_with_CART()
    {
        $trainData = Instances::createFromARFF(__DIR__ . "/../Arff/iris.arff");
        $learner = new SklearnLearner("CART");
        $model = $learner->initModel();
        $model->fit($trainData, $learner);
        echo "MODEL:" . PHP_EOL . $model . PHP_EOL;
        $this->assertNotNull($model);
        $this->assertGreaterThan(0, count($model->getRules()));
        $this->assertStringContainsString("RuleBasedModel", (string) $model);
    }
}