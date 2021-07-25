<?php

namespace Spatie\GitHubWebhooks\Http\Controllers;

use Illuminate\Http\Request;

use Spatie\GitHubWebhooks\GitHubSignatureValidator;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Symfony\Component\HttpFoundation\Response;

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
            'webhook_model' => config('github-webhooks.model'),
            'process_webhook_job' => config('github-webhooks.job'),
            'store_headers' => [
                'X-GitHub-Event',
                'X-GitHub-Delivery',
            ],
        ]);

        try {
            (new WebhookProcessor($request, $webhookConfig))->process();
        } catch (InvalidWebhookSignature) {
            return response()->json(['message' => 'invalid signature'], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['message' => 'ok']);
    }
}
