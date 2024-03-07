<?php

use Spatie\GitHubWebhooks\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function addSignature(array $payload = [], array $headers = []): array
{
    $signingSecret = config('github-webhooks.signing_secret');

    $signature = hash_hmac('sha256', json_encode($payload), $signingSecret);

    $headers['X-Hub-Signature-256'] = "sha256={$signature}";

    return $headers;
}
