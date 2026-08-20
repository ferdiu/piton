<?php

namespace aclai\piton\Tests\Unit;

use aclai\piton\Antecedents\Antecedent;
use aclai\piton\Antecedents\ContinuousAntecedent;
use aclai\piton\Antecedents\DiscreteAntecedent;
use aclai\piton\Attributes\ContinuousAttribute;
use aclai\piton\Attributes\DiscreteAttribute;
use aclai\piton\Instances\Instances;
use aclai\piton\Rules\ClassificationRule;
use aclai\piton\Tests\TestCase;

class AntecedentsUnitTest extends TestCase
{
    use CreatesTestInstances;

    public function test_create_from_attribute_dispatches_to_correct_subclass()
    {
        $continuous = new ContinuousAttribute('x', 'float');
        $discrete = new DiscreteAttribute('color', 'enum', ['red', 'blue']);

        $this->assertInstanceOf(ContinuousAntecedent::class, Antecedent::createFromAttribute($continuous));
        $this->assertInstanceOf(DiscreteAntecedent::class, Antecedent::createFromAttribute($discrete));
    }

    public function test_continuous_antecedent_from_string_parses_operator_and_value()
    {
        $ant = ContinuousAntecedent::fromString('x <= 5.5');

        $this->assertSame('x', $ant->getAttribute()->getName());
        $this->assertSame(5.5, $ant->getSplitPoint());
    }

    public function test_continuous_antecedent_covers_less_or_equal()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 6.0);

        $this->assertTrue($ant->covers($instances, 4));
        $this->assertFalse($ant->covers($instances, 8));
    }

    public function test_continuous_antecedent_covers_greater_or_equal()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue(1);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 6.0);

        $this->assertTrue($ant->covers($instances, 8));
        $this->assertFalse($ant->covers($instances, 4));
    }

    public function test_continuous_antecedent_covers_greater_than()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue(2);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 6.0);

        $this->assertTrue($ant->covers($instances, 7));
        $this->assertFalse($ant->covers($instances, 6));
    }

    public function test_continuous_antecedent_covers_less_than()
    {
        $instances = $this->makeBinaryContinuousInstances(4);
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);
        $ant->setValue(3);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 6.0);

        $this->assertTrue($ant->covers($instances, 5));
        $this->assertFalse($ant->covers($instances, 6));
    }

    public function test_continuous_antecedent_missing_value_is_covered()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $xAttr = new ContinuousAttribute('x', 'float');
        $instances = new Instances([$classAttr, $xAttr], [
            1 => [0, null],
            2 => [1, 10.0],
        ]);

        $ant = new ContinuousAntecedent($xAttr);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 5.0);

        $this->assertTrue($ant->covers($instances, 1));
    }

    public function test_continuous_antecedent_to_string_and_serialize()
    {
        $attr = new ContinuousAttribute('x', 'float');
        $ant = new ContinuousAntecedent($attr);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 5.0);

        $this->assertSame('x <= 5', $ant->toString(true));
        $this->assertSame('x <= 5', $ant->serialize());
    }

    public function test_continuous_antecedent_serialize_to_array_and_json_logic()
    {
        $attr = new ContinuousAttribute('x', 'float');
        $attr->setIndex(1);
        $ant = new ContinuousAntecedent($attr);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $method = $reflection->getMethod('setSplitPoint');
        $method->setAccessible(true);
        $method->invoke($ant, 5.0);

        $arr = $ant->serializeToArray();
        $this->assertSame(1, $arr['feature_id']);
        $this->assertSame('x', $arr['feature']);
        $this->assertSame(' <= ', $arr['operator']);
        $this->assertSame(5.0, $arr['value']);

        $json = $ant->serializeToJsonLogic();
        $this->assertSame(['x', 5.0], array_values($json['<=']));
    }

    public function test_continuous_antecedent_create_from_array()
    {
        $ant = ContinuousAntecedent::createFromArray([
            'feature_id' => 1,
            'feature' => 'x',
            'operator' => '<=',
            'value' => 5.0,
        ]);

        $this->assertSame('x', $ant->getAttribute()->getName());
        $this->assertSame(5.0, $ant->getSplitPoint());
    }

    public function test_continuous_antecedent_split_data_returns_two_bags()
    {
        $instances = $this->makeBinaryContinuousInstances(8);
        $ant = new ContinuousAntecedent($instances->getAttributes()[1]);

        $split = $ant->splitData($instances, 0.5, 0);

        $this->assertIsArray($split);
        $this->assertCount(2, $split);
        $this->assertGreaterThan(0, $split[0]->numInstances());
        $this->assertGreaterThan(0, $split[1]->numInstances());
    }

    public function test_discrete_antecedent_from_string_parses_equality()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $classAttr->setIndex(0);
        $colorAttr = new DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $colorAttr->setIndex(1);
        $attrsMap = ['color' => 1];
        $attributes = [$classAttr, $colorAttr];
        $ant = DiscreteAntecedent::fromString('(color = red)', $attrsMap, $attributes);

        $this->assertSame('color', $ant->getAttribute()->getName());
        $this->assertSame(0, $ant->getValue());
    }

    public function test_discrete_antecedent_covers_equal_value()
    {
        $instances = $this->makeDiscreteInstances();
        $colorAttr = $instances->getAttributes(false)[0];
        $ant = new DiscreteAntecedent($colorAttr);
        $ant->setValue(0);

        $this->assertTrue($ant->covers($instances, 1));
        $this->assertFalse($ant->covers($instances, 5));
    }

    public function test_discrete_antecedent_covers_inequality_when_sign_is_set()
    {
        $instances = $this->makeDiscreteInstances();
        $colorAttr = $instances->getAttributes(false)[0];
        $ant = new DiscreteAntecedent($colorAttr);
        $ant->setValue(0);
        $reflection = new \ReflectionClass($ant);
        $prop = $reflection->getProperty('sign');
        $prop->setAccessible(true);
        $prop->setValue($ant, 1);

        $this->assertFalse($ant->covers($instances, 1));
        $this->assertTrue($ant->covers($instances, 5));
    }

    public function test_discrete_antecedent_to_string_and_serialize()
    {
        $attr = new DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $ant = new DiscreteAntecedent($attr);
        $ant->setValue(0);

        $this->assertSame('color = red', $ant->toString(true));
        $this->assertSame("color == 'red'", $ant->serialize());
    }

    public function test_discrete_antecedent_serialize_to_array_and_json_logic()
    {
        $attr = new DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $attr->setIndex(1);
        $ant = new DiscreteAntecedent($attr);
        $ant->setValue(0);

        $arr = $ant->serializeToArray();
        $this->assertSame(1, $arr['feature_id']);
        $this->assertSame('color', $arr['feature']);
        $this->assertSame('==', $arr['operator']);
        $this->assertSame('red', $arr['value']);

        $json = $ant->serializeToJsonLogic();
        $this->assertSame(['color', 'red'], array_values($json['==']));
    }

    public function test_discrete_antecedent_create_from_array()
    {
        $attributes = [
            new DiscreteAttribute('class', 'enum', ['no', 'yes']),
            new DiscreteAttribute('color', 'enum', ['red', 'blue']),
        ];

        $ant = DiscreteAntecedent::createFromArray([
            'feature_id' => 1,
            'feature' => 'color',
            'operator' => '==',
            'value' => 'blue',
        ], $attributes);

        $this->assertSame('color', $ant->getAttribute()->getName());
        $this->assertSame(1, $ant->getValue());
    }

    public function test_discrete_antecedent_split_data_returns_bags_per_value()
    {
        $instances = $this->makeDiscreteInstances();
        $colorAttr = $instances->getAttributes(false)[0];
        $ant = new DiscreteAntecedent($colorAttr);

        $split = $ant->splitData($instances, 0.5, 1);

        $this->assertCount(3, $split);
        $this->assertSame(7, array_sum(array_map(fn ($b) => $b->numInstances(), $split)));
    }

    public function test_antecedent_from_string_factory_dispatches_correctly()
    {
        $continuous = Antecedent::fromString('x <= 5.0');
        $this->assertInstanceOf(ContinuousAntecedent::class, $continuous);

        $discrete = Antecedent::fromString("color = 'red'");
        $this->assertInstanceOf(DiscreteAntecedent::class, $discrete);
    }

    public function test_antecedent_clone_copies_attribute()
    {
        $attr = new ContinuousAttribute('x', 'float');
        $ant = new ContinuousAntecedent($attr);
        $clone = clone $ant;

        $this->assertNotSame($attr, $clone->getAttribute());
    }

    public function test_classification_rule_from_string_reconstructs_antecedents()
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $outputMap = array_flip($classAttr->getDomain());

        [$rule, $ruleAttributes] = ClassificationRule::fromString('(x <= 5.0) => yes', $outputMap);

        $this->assertInstanceOf(ClassificationRule::class, $rule);
        $this->assertSame(1, $rule->getConsequent());
        $this->assertCount(1, $rule->getAntecedents());
        $this->assertCount(1, $ruleAttributes);
    }
}
