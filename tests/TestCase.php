<?php

namespace aclai\piton\Tests;

use aclai\piton\PitonBaseServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            PitonBaseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'piton_connection');
        $app['config']->set('database.connections.piton_connection', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3310',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);
    }
}