<?php

namespace Spatie\GitHubWebhooks\Tests\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

class HandleIssueCreatedWebhookJob implements ShouldQueue
{
    public function __construct(
        public GitHubWebhookCall $webhookCall
    ) {
    }

    public function handle()
    {
    }
}
