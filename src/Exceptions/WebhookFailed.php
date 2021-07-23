<?php

namespace Spatie\GitHubWebhooks\Exceptions;

use Exception;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookFailed extends Exception
{
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall)
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->type} because the configured jobclass `$jobClass` does not exist.");
    }

    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
