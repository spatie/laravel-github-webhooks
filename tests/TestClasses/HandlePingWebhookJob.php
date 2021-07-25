<?php

namespace Spatie\GitHubWebhooks\Tests\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

class HandlePingWebhookJob implements ShouldQueue
{
    public function __construct(
        public GitHubWebhookCall $webhookCall
    ) {
    }

    public function handle()
    {
    }
}
