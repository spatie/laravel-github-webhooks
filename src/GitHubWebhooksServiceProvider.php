<?php

namespace Spatie\GitHubWebhooks;

use Spatie\GitHubWebhooks\Commands\GitHubWebhooksCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitHubWebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-github-webhooks')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-github-webhooks_table')
            ->hasCommand(GitHubWebhooksCommand::class);
    }
}
