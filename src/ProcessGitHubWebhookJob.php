<?php

namespace Spatie\GitHubWebhooks;

use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\GitHubWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\ProcessWebhookJob;

class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    public GitHubWebhookCall|WebhookCall $webhookCall;

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
            ->each(function (string $jobClassName) {
                if (!class_exists($jobClassName)) {
                    throw WebhookFailed::jobClassDoesNotExist($jobClassName, $this->webhookCall);
                }
            })
            ->each(fn(string $jobClassName) => dispatch(new $jobClassName($this->webhookCall)));
    }
}
