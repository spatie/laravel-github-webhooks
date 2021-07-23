<?php

namespace Spatie\GitHubWebhooks;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\GitHubWebhooks\Commands\GitHubWebhooksCommand;

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
