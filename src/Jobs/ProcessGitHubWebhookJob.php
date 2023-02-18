<?php

namespace Spatie\GitHubWebhooks\Jobs;

use function collect;
use function dispatch;
use function event;

use Spatie\GitHubWebhooks\Exceptions\JobClassDoesNotExist;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    public GitHubWebhookCall | WebhookCall $webhookCall;

    public function handle()
    {
        event("github-webhooks::{$this->webhookCall->eventName()}", $this->webhookCall);
        event("github-webhooks::{$this->webhookCall->eventActionName()}", $this->webhookCall);

        collect(config('github-webhooks.jobs'))
            ->filter(function (string $jobClassName, $eventActionName) {
                if ($eventActionName === '*') {
                    return true;
                }

                return in_array($eventActionName, [
                    $this->webhookCall->eventName(),
                    $this->webhookCall->eventActionName(),
                ]);
            })
            ->each(function (string $jobClassName) {
                if (! class_exists($jobClassName)) {
                    throw JobClassDoesNotExist::make($jobClassName);
                }
            })
            ->each(fn (string $jobClassName) => dispatch(new $jobClassName($this->webhookCall)));
    }
}
