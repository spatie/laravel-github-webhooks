<?php

namespace Spatie\GitHubWebhooks\Exceptions;

use Exception;

class JobClassDoesNotExist extends Exception
{
    public static function make(string $nonExistingClass): self
    {
        return new static("The configured job class `{$nonExistingClass}` does not exists.");
    }
}
