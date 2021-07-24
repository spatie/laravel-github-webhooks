<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::githubWebhooks('webhooks');

    config()->set('github-webhooks.signing_secret', 'abc123');
});

it('will accept a webhook with a valid signature', function () {
    $headers = ['X-GitHub-Event' => 'issues'];

    $payload = ['a' => 1];

    $this
        ->postJson('webhooks', $payload, addSignature($payload, $headers))
        ->assertSuccessful();
});

it('will not accept a webhook with a valid signature', function () {
    $headers = [
        'X-GitHub-Event' => 'issues',
        'X-Hub-Signature-256' => 'invalid-signature',
    ];

    $payload = ['a' => 1];

    $this
        ->postJson('webhooks', $payload, $headers)
        ->assertForbidden();
});
