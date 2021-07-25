<?php

namespace Spatie\GitHubWebhooks;

use Illuminate\Support\Facades\Route;
use Spatie\GitHubWebhooks\Http\Controllers\GitHubWebhooksController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitHubWebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-github-webhooks')
            ->hasConfigFile()
            ->hasMigration('create_github_webhook_calls_table');
    }

    public function bootingPackage()
    {
        Route::macro('githubWebhooks', function ($url) {
            return Route::post($url, GitHubWebhooksController::class);
        });
    }
}
