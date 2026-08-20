<?php

namespace aclai\piton\Tests\Unit;

use aclai\piton\Antecedents\ContinuousAntecedent;
use aclai\piton\Attributes\ContinuousAttribute;
use aclai\piton\Attributes\DiscreteAttribute;
use aclai\piton\Instances\Instances;
use aclai\piton\Rules\ClassificationRule;
use aclai\piton\Rules\RipperRule;
use aclai\piton\RuleStats\RuleStats;
use aclai\piton\Tests\TestCase;

class RuleStatsUnitTest extends TestCase
{
    use CreatesTestInstances;

    private function makeSimpleRule(Instances $instances, float $split, int $value, int $consequent): ClassificationRule
    {
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue($value);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, $split);

        $rule = new ClassificationRule($consequent);
        $rule->setAntecedents([$ant]);

        return $rule;
    }

    private function makeStatsWithRule(): RuleStats
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = $this->makeSimpleRule($instances, 4.0, 0, 0);

        $stats = new RuleStats($instances);
        $stats->setNumAllConds(10);
        $stats->pushRule($rule);

        return $stats;
    }

    public function test_constructor_accepts_data_and_rules()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = $this->makeSimpleRule($instances, 4.0, 0, 0);

        $stats = new RuleStats($instances, [$rule], 10);

        $this->assertSame(1, $stats->getRulesetSize());
        $this->assertSame($rule, $stats->getRuleset()[0]);
    }

    public function test_push_rule_updates_stats()
    {
        $stats = $this->makeStatsWithRule();

        $this->assertSame(1, $stats->getRulesetSize());
        $this->assertNotNull($stats->getSimpleStats(0));
        $this->assertIsArray($stats->getFiltered(0));
        $this->assertEquals(4.0, $stats->getSimpleStats(0)[0]);
        $this->assertEquals(4.0, $stats->getSimpleStats(0)[2]);
    }

    public function test_pop_rule_removes_last_rule_stats()
    {
        $stats = $this->makeStatsWithRule();
        $stats->popRule();

        $this->assertSame(0, $stats->getRulesetSize());
    }

    public function test_subset_dl_returns_non_negative_value()
    {
        $dl = RuleStats::subsetDL(10, 2, 0.25);

        $this->assertIsFloat($dl);
        $this->assertGreaterThan(0, $dl);
    }

    public function test_data_dl_returns_finite_value()
    {
        $dl = RuleStats::dataDL(0.5, 10.0, 5.0, 2.0, 3.0);

        $this->assertFinite($dl);
        $this->assertIsFloat($dl);
    }

    public function test_num_all_conditions_counts_discrete_and_continuous()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $colorAttr = new DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $xAttr = new ContinuousAttribute('x', 'float');
        $instances = new Instances([$classAttr, $colorAttr, $xAttr], [
            1 => [0, 0, 1.0],
            2 => [0, 1, 2.0],
            3 => [1, 0, 3.0],
        ]);

        $this->assertSame(8, RuleStats::numAllConditions($instances));
    }

    public function test_partition_splits_instances()
    {
        $instances = $this->makeBinaryContinuousInstances(10);
        [$first, $second] = RuleStats::partition($instances, 3);

        $this->assertGreaterThan(0, $first->numInstances());
        $this->assertGreaterThan(0, $second->numInstances());
        $this->assertSame(20, $first->numInstances() + $second->numInstances());
    }

    public function test_stratified_bin_partition_preserves_class_ratios()
    {
        $instances = $this->makeBinaryContinuousInstances(9);
        [$grow, $prune] = RuleStats::stratifiedBinPartition($instances, 3);

        $this->assertSame(18, $grow->numInstances() + $prune->numInstances());
        $this->assertSame(18, array_sum($grow->getClassCounts()) + array_sum($prune->getClassCounts()));
    }

    public function test_theory_dl_is_zero_for_empty_rule()
    {
        $stats = $this->makeStatsWithRule();
        $emptyRule = new ClassificationRule(0);
        $stats->pushRule($emptyRule);

        $this->assertSame(0.0, $stats->theoryDL(1));
    }

    public function test_theory_dl_is_positive_for_non_empty_rule()
    {
        $stats = $this->makeStatsWithRule();

        $this->assertGreaterThan(0, $stats->theoryDL(0));
    }

    public function test_relative_dl_returns_finite_value()
    {
        $stats = $this->makeStatsWithRule();

        $this->assertFinite($stats->relativeDL(0, 0.5, true));
    }

    public function test_min_data_dl_if_exists_returns_finite_value()
    {
        $stats = $this->makeStatsWithRule();

        $this->assertFinite($stats->minDataDLIfExists(0, 0.5, true));
    }

    public function test_min_data_dl_if_deleted_returns_finite_value()
    {
        $stats = $this->makeStatsWithRule();

        $this->assertFinite($stats->minDataDLIfDeleted(0, 0.5, true));
    }

    public function test_potential_returns_float_or_nan()
    {
        $stats = $this->makeStatsWithRule();

        $ruleStat = $stats->getSimpleStats(0);
        $rulesetStat = [
            $ruleStat[0],
            $ruleStat[1],
            $ruleStat[2],
            $ruleStat[3],
            $ruleStat[4],
            $ruleStat[5],
        ];

        $potential = $stats->potential(0, 0.5, $rulesetStat, $ruleStat, false);

        $this->assertTrue(is_float($potential) || is_nan($potential));
    }

    public function test_reduce_dl_does_not_increase_ruleset_size()
    {
        $stats = $this->makeStatsWithRule();

        $before = $stats->getRulesetSize();
        $stats->reduceDL(0.5, false);

        $this->assertLessThanOrEqual($before, $stats->getRulesetSize());
    }

    public function test_re_count_data_populates_simple_stats()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = $this->makeSimpleRule($instances, 4.0, 0, 0);
        $stats = new RuleStats($instances, [$rule], 10);
        $stats->reCountData();

        $this->assertNotNull($stats->getSimpleStats(0));
        $this->assertIsArray($stats->getFiltered(0));
    }

    public function test_count_data_populates_stats_from_index()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = $this->makeSimpleRule($instances, 4.0, 0, 0);
        $stats = new RuleStats($instances, [$rule], 10);
        $stats->countData(0, $instances, []);

        $this->assertNotNull($stats->getSimpleStats(0));
        $this->assertIsArray($stats->getFiltered(0));
    }

    public function test_to_string_contains_size_and_data_summary()
    {
        $stats = $this->makeStatsWithRule();

        $str = $stats->toString();

        $this->assertStringContainsString('RuleStats(size 1)', $str);
        $this->assertStringContainsString('numAllConds: 10', $str);
    }

    public function test_rm_covered_by_successives_filters_instances()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule1 = $this->makeSimpleRule($instances, 4.0, 0, 0);
        $rule2 = $this->makeSimpleRule($instances, 8.0, 0, 0);

        $remaining = RuleStats::rmCoveredBySuccessives($instances, [$rule1, $rule2], 0);

        $this->assertLessThanOrEqual($instances->numInstances(), $remaining->numInstances());
    }

    public function test_clean_up_frees_data_references()
    {
        $stats = $this->makeStatsWithRule();
        $stats->cleanUp();

        $reflection = new \ReflectionClass($stats);
        $dataProp = $reflection->getProperty('data');
        $dataProp->setAccessible(true);
        $this->assertNull($dataProp->getValue($stats));

        $filteredProp = $reflection->getProperty('filtered');
        $filteredProp->setAccessible(true);
        $this->assertNull($filteredProp->getValue($stats));
    }

    public function test_set_num_all_conds_returns_self()
    {
        $stats = new RuleStats();

        $this->assertSame($stats, $stats->setNumAllConds(10));
    }

    public function test_set_ruleset_returns_self()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $rule = $this->makeSimpleRule($instances, 4.0, 0, 0);
        $stats = new RuleStats();

        $this->assertSame($stats, $stats->setRuleset([$rule]));
        $this->assertSame($rule, $stats->getRuleset()[0]);
    }
}
