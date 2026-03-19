<?php

namespace AesirCloud\StatamicAiChatbot\Tests;

use AesirCloud\StatamicAiChatbot\StatamicAiChatbotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineRoutes($router): void
    {
        require dirname(__DIR__).'/routes/actions.php';
        $router->prefix('cp')->group(dirname(__DIR__).'/routes/cp.php');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            StatamicAiChatbotServiceProvider::class,
        ];
    }
}
