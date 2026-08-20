<?php

namespace aclai\piton\Tests\Unit;

use aclai\piton\Antecedents\Antecedent;
use aclai\piton\Antecedents\ContinuousAntecedent;
use aclai\piton\Antecedents\DiscreteAntecedent;
use aclai\piton\Attributes\ContinuousAttribute;
use aclai\piton\Attributes\DiscreteAttribute;
use aclai\piton\Instances\Instances;
use aclai\piton\Rules\ClassificationRule;
use aclai\piton\Rules\RipperRule;
use aclai\piton\Tests\TestCase;

class RulesUnitTest extends TestCase
{
    use CreatesTestInstances;

    private function buildXAntecedent(Instances $instances, float $split, int $value): ContinuousAntecedent
    {
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue($value);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, $split);

        return $ant;
    }

    public function test_classification_rule_construction_and_accessors()
    {
        $rule = new ClassificationRule(1);

        $this->assertSame(1, $rule->getConsequent());
        $this->assertTrue($rule->hasConsequent());
        $this->assertSame(0, $rule->getSize());
        $this->assertFalse($rule->hasAntecedents());
        $this->assertSame([], $rule->getAntecedents());
    }

    public function test_classification_rule_set_consequent_returns_self()
    {
        $rule = new ClassificationRule(0);

        $this->assertSame($rule, $rule->setConsequent(1));
        $this->assertSame(1, $rule->getConsequent());
    }

    public function test_rule_antecedent_management()
    {
        $instances = $this->makeBinaryContinuousInstances(1);
        $ant = $this->buildXAntecedent($instances, 5.0, 0);
        $rule = new ClassificationRule(0);
        $rule->setAntecedents([$ant]);

        $this->assertSame(1, $rule->getSize());
        $this->assertTrue($rule->hasAntecedents());
        $this->assertSame([$ant], $rule->getAntecedents());
    }

    public function test_rule_to_string_without_class_attribute()
    {
        $instances = $this->makeBinaryContinuousInstances(1);
        $ant = $this->buildXAntecedent($instances, 5.0, 0);
        $rule = new ClassificationRule(0);
        $rule->setAntecedents([$ant]);

        $this->assertSame('(x <= 5) => [0]', $rule->toString());
    }

    public function test_rule_to_string_with_class_attribute()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $xAttr = new ContinuousAttribute('x', 'float');
        $ant = new ContinuousAntecedent($xAttr);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 5.0);

        $rule = new ClassificationRule(1);
        $rule->setAntecedents([$ant]);

        $this->assertSame('(x <= 5) => class=yes', $rule->toString($classAttr));
    }

    public function test_empty_rule_covers_all_instances()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = new ClassificationRule(0);

        $this->assertTrue($rule->covers($instances, 1));
        $this->assertTrue($rule->coversAll($instances));
    }

    public function test_rule_covers_respects_all_antecedents()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = $this->buildXAntecedent($instances, 4.0, 0);
        $rule = new ClassificationRule(0);
        $rule->setAntecedents([$ant]);

        $this->assertTrue($rule->covers($instances, 1));
        $this->assertFalse($rule->covers($instances, 8));
    }

    public function test_rule_coverage_returns_per_antecedent_results()
    {
        $instances = $this->makeMixedInstances();
        $colorAnt = Antecedent::createFromAttribute($instances->getAttributes()[1]);
        $colorAnt->setValue(1);
        $xAnt = Antecedent::createFromAttribute($instances->getAttributes()[2]);
        $xAnt->setValue(0);
        $reflection = new \ReflectionClass($xAnt);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($xAnt, 10.0);

        $rule = new ClassificationRule(1);
        $rule->setAntecedents([$colorAnt, $xAnt]);

        $coverage = $rule->coverage($instances, 4);
        $this->assertSame([true, true], $coverage);

        $coverage = $rule->coverage($instances, 1);
        $this->assertSame([false, true], $coverage);
    }

    public function test_rule_get_non_covering_sub_rule()
    {
        $instances = $this->makeMixedInstances();
        $colorAnt = Antecedent::createFromAttribute($instances->getAttributes()[1]);
        $colorAnt->setValue(1);
        $xAnt = Antecedent::createFromAttribute($instances->getAttributes()[2]);
        $xAnt->setValue(1);
        $reflection = new \ReflectionClass($xAnt);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($xAnt, 7.0);

        $rule = new ClassificationRule(1);
        $rule->setAntecedents([$colorAnt, $xAnt]);

        $subRule = $rule->getNonCoveringSubRule($instances, 1, [false, true]);

        $this->assertSame(1, $subRule->getSize());
        $this->assertSame($colorAnt, $subRule->getAntecedents()[0]);
    }

    public function test_classification_rule_compute_measures()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = $this->buildXAntecedent($instances, 4.0, 0);
        $rule = new ClassificationRule(0);
        $rule->setAntecedents([$ant]);

        $measures = $rule->computeMeasures($instances);

        $this->assertArrayHasKey('covered', $measures);
        $this->assertArrayHasKey('support', $measures);
        $this->assertArrayHasKey('confidence', $measures);
        $this->assertArrayHasKey('lift', $measures);
        $this->assertArrayHasKey('conviction', $measures);
        $this->assertSame(4, $measures['covered']);
        $this->assertSame(1.0, $measures['confidence']);
    }

    public function test_classification_rule_compute_measures_returns_filtered_data()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = $this->buildXAntecedent($instances, 4.0, 0);
        $rule = new ClassificationRule(0);
        $rule->setAntecedents([$ant]);

        $measures = $rule->computeMeasures($instances, true);

        $this->assertInstanceOf(Instances::class, $measures['filteredData']);
        $this->assertSame(4, $measures['filteredData']->numInstances());
    }

    public function test_ripper_rule_compute_def_accu_counts_target_class()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = new RipperRule(0);

        $this->assertEquals(4.0, $rule->computeDefAccu($instances));
    }

    public function test_ripper_rule_grow_adds_antecedents()
    {
        $instances = $this->makeBinaryContinuousInstances(8);
        $rule = new RipperRule(0);

        $rule->grow($instances, 2.0);

        $this->assertGreaterThan(0, $rule->getSize());
        $this->assertSame(0, $rule->getConsequent());
    }

    public function test_ripper_rule_prune_does_not_throw()
    {
        $instances = $this->makeBinaryContinuousInstances(8);
        $rule = new RipperRule(0);
        $rule->grow($instances, 2.0);

        $originalSize = $rule->getSize();
        $rule->prune($instances, false);

        $this->assertLessThanOrEqual($originalSize, $rule->getSize());
    }

    public function test_ripper_rule_clean_up_removes_redundant_tests()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $xAttr = new ContinuousAttribute('x', 'float');
        $instances = new Instances([$classAttr, $xAttr], [
            1 => [0, 1.0],
            2 => [0, 2.0],
            3 => [1, 10.0],
            4 => [1, 11.0],
        ]);

        $ant1 = $this->buildXAntecedent($instances, 5.0, 0);
        $ant2 = $this->buildXAntecedent($instances, 3.0, 0);
        $rule = new RipperRule(0);
        $rule->setAntecedents([$ant1, $ant2]);

        $rule->cleanUp($instances);

        $this->assertSame(1, $rule->getSize());
    }

    public function test_ripper_rule_clone_copies_antecedents()
    {
        $instances = $this->makeBinaryContinuousInstances(1);
        $ant = $this->buildXAntecedent($instances, 5.0, 0);
        $rule = new RipperRule(0);
        $rule->setAntecedents([$ant]);

        $clone = clone $rule;

        $this->assertCount(1, $clone->getAntecedents());
        $this->assertNotSame($ant, $clone->getAntecedents()[0]);
    }
}
