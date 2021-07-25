<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;
use Spatie\GitHubWebhooks\Exceptions\JobClassDoesNotExist;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleAllIssuesWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleAllWebhooksJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleIssueClosedWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleIssueCreatedWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandlePingWebhookJob;

beforeEach(function () {
    Route::githubWebhooks('webhooks');

    config()->set('github-webhooks.signing_secret', 'abc123');

    Bus::fake([
        HandleAllIssuesWebhookJob::class,
        HandleIssueCreatedWebhookJob::class,
        HandlePingWebhookJob::class,
        HandleAllWebhooksJob::class,
    ]);
});

it('will accept a webhook with a valid signature', function () {
    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = preparePayload(['a' => 1]);

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();
});

it('will not accept a webhook with an invalid signature', function () {
    $headers = [
        'X-GitHub-Event' => 'issues',
        'X-Hub-Signature-256' => 'invalid-signature',
    ];

    $payload = preparePayload(['a' => 1]);

    $this
        ->postJson('webhooks', $payload, $headers)
        ->assertForbidden();
});

it('will accept a webhook with an invalid signature when validation is turned off', function () {
    config()->set('github-webhooks.verify_signature', false);

    $headers = [
        'X-GitHub-Event' => 'issues',
        'X-Hub-Signature-256' => 'invalid-signature',
    ];

    $payload = preparePayload(['a' => 1]);

    $this
        ->postJson('webhooks', $payload, $headers)
        ->assertSuccessful();
});

it('will dispatch a single job when it matches the event name', function () {
    config()->set('github-webhooks.jobs', [
        'issues' => HandleAllIssuesWebhookJob::class,
        'issue.created' => HandleIssueCreatedWebhookJob::class,
        'ping' => HandlePingWebhookJob::class,
    ]);

    $headers = ['X-GitHub-Event' => 'ping'];

    $payload = preparePayload([]);

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();

    Bus::assertDispatched(HandlePingWebhookJob::class);
    Bus::assertNotDispatched(HandleIssueCreatedWebhookJob::class);
    Bus::assertNotDispatched(HandleAllIssuesWebhookJob::class);
});

it('will dispatch a both the event job and eventAction job when it matches the eventAction name', function () {
    config()->set('github-webhooks.jobs', [
        'issues' => HandleAllIssuesWebhookJob::class,
        'issues.created' => HandleIssueCreatedWebhookJob::class,
        'issues.closed' => HandleIssueClosedWebhookJob::class,

        'ping' => HandlePingWebhookJob::class,
    ]);

    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = preparePayload(['action' => 'created']);

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();

    Bus::assertDispatched(HandleIssueCreatedWebhookJob::class);
    Bus::assertDispatched(HandleAllIssuesWebhookJob::class);
    Bus::assertNotDispatched(HandlePingWebhookJob::class);
    Bus::assertNotDispatched(HandleIssueClosedWebhookJob::class);
});

it('offers a wildcard to process all webhooks', function () {
    config()->set('github-webhooks.jobs', [
        '*' => HandleAllWebhooksJob::class,
        'ping' => HandlePingWebhookJob::class,
    ]);

    $headers = ['X-GitHub-Event' => 'ping'];

    $payload = preparePayload([]);

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();

    Bus::assertDispatched(HandleAllWebhooksJob::class);
    Bus::assertDispatched(HandlePingWebhookJob::class);
});

it('will throw an exception when a non-existing job class is used', function () {
    $this->withoutExceptionHandling();

    config()->set('github-webhooks.jobs', [
        'issues.created' => NonExistingClass::class,
    ]);

    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = preparePayload(['action' => 'created']);

    $this->postJson('webhooks', $payload, addSignature($payload, $headers));
})->throws(JobClassDoesNotExist::class);
