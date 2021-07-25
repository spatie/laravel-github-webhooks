<?php

namespace Spatie\GitHubWebhooks\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\GitHubWebhooks\GitHubWebhooksServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            GitHubWebhooksServiceProvider::class,
            RayServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__ . '/../database/migrations/create_github_webhook_calls_table.php.stub';

        $migration->up();
    }
}
