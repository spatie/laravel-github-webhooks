<?php

namespace Spatie\GitHubWebhooks;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Spatie\GitHubWebhooks\GitHubWebhooks
 */
class GitHubWebhooksFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-github-webhooks';
    }
}
