<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

use aclai\piton\Tests\TestCase;
use aclai\piton\Instances\Instances;

class InstancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_object_of_type_instances_can_be_created_from_an_arff_file()
    {
        $trainData = Instances::createFromARFF(__DIR__ . "/../Arff/iris.arff");
        $outputPath = __DIR__ . "/../Arff/myIris.arff";
        $trainData->saveToARFF($outputPath);
        $this->assertFileExists($outputPath);
    }

    public function test_an_object_of_type_instances_can_be_saved_to_db()
    {
        $trainData = Instances::createFromARFF(__DIR__."/../Arff/iris.arff");
        $trainData->saveToDB("myIris");
        $this->assertCount(100, DB::connection('piton_connection')->table("myIris")->get());
    }
}