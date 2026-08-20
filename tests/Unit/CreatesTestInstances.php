<?php

namespace aclai\piton\Tests\Unit;

use aclai\piton\Attributes\ContinuousAttribute;
use aclai\piton\Attributes\DiscreteAttribute;
use aclai\piton\Instances\Instances;

trait CreatesTestInstances
{
    /**
     * Build a simple binary dataset with one continuous attribute.
     * Class 0 corresponds to x <= 6, class 1 to x > 6.
     */
    protected function makeBinaryContinuousInstances(int $instancesPerClass = 8): Instances
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $xAttr = new ContinuousAttribute('x', 'float');

        $data = [];
        $id = 1;
        for ($i = 0; $i < $instancesPerClass; $i++) {
            $data[$id] = [0, (float) ($i + 1)];
            $id++;
        }
        for ($i = 0; $i < $instancesPerClass; $i++) {
            $data[$id] = [1, (float) ($instancesPerClass + $i + 1)];
            $id++;
        }

        return new Instances([$classAttr, $xAttr], $data);
    }

    /**
     * Build a dataset with a discrete descriptive attribute.
     */
    protected function makeDiscreteInstances(): Instances
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['n', 'y']);
        $colorAttr = new DiscreteAttribute('color', 'enum', ['red', 'blue', 'green']);

        $data = [
            1 => [0, 0],
            2 => [0, 0],
            3 => [0, 1],
            4 => [0, 1],
            5 => [1, 1],
            6 => [1, 2],
            7 => [1, 2],
        ];

        return new Instances([$classAttr, $colorAttr], $data);
    }

    /**
     * Build a dataset with one continuous and one discrete descriptive attribute.
     */
    protected function makeMixedInstances(): Instances
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $colorAttr = new DiscreteAttribute('color', 'enum', ['red', 'blue']);
        $xAttr = new ContinuousAttribute('x', 'float');

        $data = [
            1 => [0, 0, 1.0],
            2 => [0, 0, 2.0],
            3 => [0, 1, 3.0],
            4 => [1, 1, 8.0],
            5 => [1, 1, 9.0],
            6 => [1, 0, 10.0],
        ];

        return new Instances([$classAttr, $colorAttr, $xAttr], $data);
    }

    /**
     * Build a weighted version of the binary continuous dataset.
     */
    protected function makeWeightedInstances(): Instances
    {
        $classAttr = new DiscreteAttribute('class', 'enum', ['no', 'yes']);
        $xAttr = new ContinuousAttribute('x', 'float');

        $data = [
            1 => [0, 1.0],
            2 => [0, 2.0],
            3 => [1, 10.0],
            4 => [1, 11.0],
        ];
        $weights = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        return new Instances([$classAttr, $xAttr], $data, $weights);
    }
}
