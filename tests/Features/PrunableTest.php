<?php

use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

use function Spatie\PestPluginTestTime\testTime;

it('will prune records after the configured amount of days', function () {
    testTime()->freeze();

    config()->set('github-webhooks.prune_webhook_calls_after_days', 5);

    GitHubWebhookCall::create([
        'name' => 'dummy name',
        'url' => 'https://example.com',
    ]);

    testTime()->addDays(5)->subSecond();
    $this->artisan('model:prune');
    expect(GitHubWebhookCall::count())->toBe(1);

    testTime()->addSecond();
    $this->artisan('model:prune', [
        '--model' => [GitHubWebhookCall::class],
    ]);
    expect(GitHubWebhookCall::count())->toBe(0);
});
