<?php

namespace aclai\piton\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use aclai\piton\Facades\Piton;
use aclai\piton\Facades\Utils;
use aclai\piton\PitonBaseServiceProvider;
use aclai\piton\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_piton_commands_are_registered()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('piton:create_example', $commands);
        $this->assertArrayHasKey('piton:predict_by_identifier', $commands);
        $this->assertArrayHasKey('piton:update_models', $commands);
        $this->assertArrayHasKey('piton:update_models_with_interface', $commands);
    }

    public function test_publish_groups_are_registered()
    {
        $paths = ServiceProvider::pathsToPublish(PitonBaseServiceProvider::class);

        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);

        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'problem-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'prip-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'sklearn_cart-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'wittgenstein_irep-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'wittgenstein_ripperk-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(null, 'iris-config'));
    }

    public function test_utils_facade_resolves_and_runs_join_paths()
    {
        $this->assertSame('a/b/c', Utils::join_paths('a', 'b', 'c'));
    }

    public function test_piton_facade_resolves()
    {
        $this->assertTrue(Piton::configNotPublished());
    }
}
