<?php

namespace aclai\piton\Tests\Unit;

use aclai\piton\DiscriminativeModels\RuleBasedModel;
use aclai\piton\Instances\Instances;
use aclai\piton\Learners\PRip;
use aclai\piton\Tests\TestCase;

class PRipUnitTest extends TestCase
{
    use CreatesTestInstances;

    public function test_constructor_sets_default_hyperparameters()
    {
        $learner = new PRip();

        $this->assertSame(2, $learner->getNumOptimizations());
        $this->assertSame(3, $learner->getNumFolds());
        $this->assertSame(2.0, $learner->getMinNo());
    }

    public function test_init_model_returns_rule_based_model()
    {
        $learner = new PRip();

        $this->assertInstanceOf(RuleBasedModel::class, $learner->initModel());
    }

    public function test_setters_return_self()
    {
        $learner = new PRip();

        $this->assertSame($learner, $learner->setNumOptimizations(1));
        $this->assertSame($learner, $learner->setNumFolds(2));
        $this->assertSame($learner, $learner->setMinNo(1.0));
        $this->assertSame(1, $learner->getNumOptimizations());
        $this->assertSame(2, $learner->getNumFolds());
        $this->assertSame(1.0, $learner->getMinNo());
    }

    public function test_check_stop_returns_true_when_dl_surplus_exceeded()
    {
        $learner = new PRip();
        $reflection = new \ReflectionMethod($learner, 'checkStop');
        $reflection->setAccessible(true);

        $rst = [0, 0, 1, 0, 0, 0];

        $this->assertTrue($reflection->invoke($learner, $rst, 10.0, 100.0));
    }

    public function test_check_stop_returns_true_when_no_positives()
    {
        $learner = new PRip();
        $reflection = new \ReflectionMethod($learner, 'checkStop');
        $reflection->setAccessible(true);

        $rst = [0, 0, 0, 0, 0, 0];

        $this->assertTrue($reflection->invoke($learner, $rst, 10.0, 10.0));
    }

    public function test_check_stop_returns_true_when_error_rate_high_and_checking_enabled()
    {
        $learner = new PRip();
        $reflection = new \ReflectionMethod($learner, 'checkStop');
        $reflection->setAccessible(true);

        $rst = [4, 0, 2, 0, 2, 0];

        $this->assertTrue($reflection->invoke($learner, $rst, 10.0, 10.0));
    }

    public function test_check_stop_returns_false_when_rule_is_acceptable()
    {
        $learner = new PRip();
        $reflection = new \ReflectionMethod($learner, 'checkStop');
        $reflection->setAccessible(true);

        $rst = [4, 0, 4, 0, 0, 0];

        $this->assertFalse($reflection->invoke($learner, $rst, 10.0, 10.0));
    }

    public function test_config_not_published_without_published_config()
    {
        $this->assertTrue(PRip::configNotPublished());
    }

    public function test_teacher_trains_model_on_simple_binary_data()
    {
        $learner = new PRip();
        $learner->setNumOptimizations(0);
        $learner->setNumFolds(2);
        $learner->setMinNo(1.0);

        $instances = $this->makeBinaryContinuousInstances(8);
        $model = $learner->initModel();
        $model->fit($instances, $learner);

        $rules = $model->getRules();
        $this->assertNotEmpty($rules);
        $this->assertSame('PRip', $learner->getName());
    }

    public function test_teacher_can_handle_discrete_attribute()
    {
        $classAttr = new \aclai\piton\Attributes\DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $colorAttr = new \aclai\piton\Attributes\DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $data = [];
        $id = 1;
        for ($i = 0; $i < 8; $i++) {
            $data[$id++] = [0, 0];
        }
        for ($i = 0; $i < 8; $i++) {
            $data[$id++] = [1, 1];
        }
        $instances = new Instances([$classAttr, $colorAttr], $data);

        $learner = new PRip();
        $learner->setNumOptimizations(0);
        $learner->setNumFolds(2);
        $learner->setMinNo(1.0);

        $model = $learner->initModel();
        $model->fit($instances, $learner);

        $this->assertNotEmpty($model->getRules());
    }
}
