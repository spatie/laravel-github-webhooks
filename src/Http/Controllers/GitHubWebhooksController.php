<?php

namespace Spatie\GitHubWebhooks\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\GitHubWebhooks\GitHubSignatureValidator;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use function config;
use function response;

class GitHubWebhooksController
{
    public function __invoke(Request $request)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'GitHub',
            'signing_secret' => config('github-webhooks.signing_secret'),
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => GitHubSignatureValidator::class,
            'webhook_profile' => config('github-webhooks.profile'),
            'webhook_model' => WebhookCall::class,
            'process_webhook_job' => config('github-webhooks.model'),
            'store_headers' => [
                'X-GitHub-Event',
                'X-GitHub-Delivery',
            ],
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
