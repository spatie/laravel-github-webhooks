<?php

namespace Spatie\GitHubWebhooks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Spatie\WebhookClient\Models\WebhookCall;

class GitHubWebhookCall extends WebhookCall
{
    use MassPrunable;

    public $table = 'github_webhook_calls';

    public function eventName(): string
    {
        return $this->headerBag()->get('X-GitHub-Event') ?? '';
    }

    public function eventActionName(): string
    {
        $actionName = $this->webhookCall['payload']['action'] ?? null;

        if (! $actionName) {
            return $this->eventName();
        }

        return "{$this->eventName()}.$actionName";
    }

    public function prunable(): Builder
    {
        $pruneAfterDays = config('github-webhooks.prune_webhook_calls_after_days');

        return static::query()->where('created_at', '<=', now()->subDays($pruneAfterDays));
    }
}
