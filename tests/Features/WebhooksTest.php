<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\GitHubWebhooks\Exceptions\JobClassDoesNotExist;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleAllIssuesWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleAllWebhooksJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleIssueClosedWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandleIssueCreatedWebhookJob;
use Spatie\GitHubWebhooks\Tests\TestClasses\HandlePingWebhookJob;
use Symfony\Component\HttpFoundation\HeaderBag;

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

    $payload = ['a' => 1];

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();
});

it('will not accept a webhook with an invalid signature', function () {
    $headers = [
        'X-GitHub-Event' => 'issues',
        'X-Hub-Signature-256' => 'invalid-signature',
    ];

    $payload = ['a' => 1];

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

    $payload = ['a' => 1];

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

    $payload = [];

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

    $payload = ['action' => 'created'];

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

    $payload = [];

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

    $payload = ['action' => 'created'];

    $this->postJson('webhooks', $payload, addSignature($payload, $headers));
})->throws(JobClassDoesNotExist::class);

it('will store a model on a successful webhook request', function () {
    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = [
        'action' => 'opened',
        'deeply' => ['nested' => 'value'],
    ];

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();

    expect(GitHubWebhookCall::count())->toBe(1);

    /** @var GitHubWebhookCall $gitHubWebhookCall */
    $gitHubWebhookCall = GitHubWebhookCall::first();

    expect($gitHubWebhookCall->eventActionName())->toBe('issues.opened');
    expect($gitHubWebhookCall->eventName())->toBe('issues');
    expect($gitHubWebhookCall->payload())->toBe([
        'action' => 'opened',
        'deeply' => ['nested' => 'value'],
    ]);
    expect($gitHubWebhookCall->payload('deeply.nested'))->toBe('value');
    expect($gitHubWebhookCall->headers())->toBeInstanceOf(HeaderBag::class);
    expect($gitHubWebhookCall->headers()->get('X-GitHub-Event'))->toBe('issues');
});

it('will fire both the github-webhooks.event and github-webhooks.eventAction events', function () {
    Event::fake();

    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = [
        'action' => 'opened',
    ];

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();

    Event::assertDispatched('github-webhooks::issues', 1);
    Event::assertDispatched('github-webhooks::issues.opened', 1);
});
