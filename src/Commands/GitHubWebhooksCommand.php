<?php

namespace Spatie\GitHubWebhooks\Commands;

use Illuminate\Console\Command;

class GitHubWebhooksCommand extends Command
{
    public $signature = 'laravel-github-webhooks';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
