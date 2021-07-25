<?php

namespace Spatie\GitHubWebhooks\Jobs;

use function collect;
use function dispatch;
use function event;
use Spatie\GitHubWebhooks\Exceptions\JobClassDoesNotExist;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\ProcessWebhookJob;

class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    public GitHubWebhookCall | WebhookCall $webhookCall;

    public function handle()
    {
        event("github-webhooks::{$this->webhookCall->eventActionName()}", $this->webhookCall);

        collect(config('github-webhooks.jobs'))
            ->filter(function (string $jobClassName, $eventActionName) {
                return in_array($eventActionName, [
                    $this->webhookCall->eventName(),
                    $this->webhookCall->eventActionName(),
                ]);
            })
            ->filter()
            ->ray()
            ->each(function (string $jobClassName) {
                if (! class_exists($jobClassName)) {
                    throw JobClassDoesNotExist::make($jobClassName);
                }
            })
            ->each(fn (string $jobClassName) => dispatch(new $jobClassName($this->webhookCall)));
    }
}
