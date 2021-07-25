# Handle GitHub Webhooks in a Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-github-webhooks.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-github-webhooks)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/spatie/laravel-github-webhooks/run-tests?label=tests)
![Check & fix styling](https://github.com/spatie/laravel-github-webhooks/workflows/Check%20&%20fix%20styling/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-github-webhooks.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-github-webhooks)

GitHub can notify your application of events using webhooks. This package can help you handle
those webhooks. 

Out of the box, it will verify the GitHub signature of all incoming requests. All valid calls will be
logged to the database. The package allows you to easily define jobs or events that should be dispatched when specific webhooks hit your
app. 

Here's an example of such a job.

```php
namespace App\Jobs\GitHubWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

class HandleIssueOpenedWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public GitHubWebhookCall $gitHubWebhookCall;

    public function __construct(
        public GitHubWebhookCall $webhookCall
    ) {}

    public function handle()
    {
        // React to the issue opened at GitHub event here

        // You can access the payload of the GitHub webhook call with `$this->webhookCall->payload`
    }
}
```

Before using this package we highly recommend
reading [the entire documentation on webhooks over at GitHub](https://docs.github.com/en/developers/webhooks-and-events/webhooks/about-webhooks).

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-github-webhooks.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-github-webhooks)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can
support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.
You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards
on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-github-webhooks
```

You must publish the config file with:

```bash
php artisan vendor:publish --provider="Spatie\GitHubWebhooks\GitHubWebhooksServiceProvider" --tag="github-webhooks-config"
```

This is the contents of the config file that will be published at `config/github-webhooks.php`:

```php
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\GitHubWebhooks\Jobs\ProcessGitHubWebhookJob;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

return [
    /*
     * GitHub will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://docs.github.com/en/developers/webhooks-and-events/webhooks/about-webhooks.
     */
    'signing_secret' => env('GITHUB_WEBHOOK_SECRET'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here.
     *
     * You can find a list of GitHub webhook types here:
     * https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads.
     * 
     * You can use "*" to let a job handle all sent webhook types
     */
    'jobs' => [
        // 'ping' => \App\Jobs\GitHubWebhooks\HandlePingWebhook::class,
        // 'issues.opened' => \App\Jobs\GitHubWebhooks\HandleIssueOpenedWebhookJob::class,
        // '*' => \App\Jobs\GitHubWebhooks\HandleAllWebhooks::class
    ],

    /*
     * This model will be used to store all incoming webhooks.
     * It should be or extend `Spatie\GitHubWebhooks\Models\GitHubWebhookCall`
     */
    'model' => GitHubWebhookCall::class,

    /*
     * When running `php artisan model:prune` all stored GitHub webhook calls
     * that were successfully processed will be deleted.
     *
     * More info on pruning: https://laravel.com/docs/8.x/eloquent#pruning-models
     */
    'prune_webhook_calls_after_days' => 10,

    /*
     * The classname of the job to be used. The class should equal or extend
     * Spatie\GitHubWebhooks\ProcessGitHubWebhookJob.
     */
    'job' => ProcessGitHubWebhookJob::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => ProcessEverythingWebhookProfile::class,

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('GITHUB_SIGNATURE_VERIFY', true),
];
```

In the `signing_secret` key of the config file you should add a valid webhook secret. You can find the secret used
at [the webhook configuration settings on the GitHub dashboard](https://dashboard.github.com/account/webhooks).

Next, you must publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\GitHubWebhooks\GitHubWebhooksServiceProvider" --tag="github-webhooks-migrations"
```

After the migration has been published, you can create the `github_webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At the GitHub webhooks settings of a repo you must
configure at what URL GitHub webhooks should be sent. In the routes file of your app you must pass that route
to the `Route::githubWebhooks` route macro:

```php
Route::githubWebhooks('webhook-route-configured-at-the-github-webhooks-settings');
```

Behind the scenes this macro will register a `POST` route to a controller provided by this package. Because GitHub has no way
of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-at-the-github-webhooks-settings',
];
```

## Usage

GitHub will send out webhooks for several event types. You can find
the [full list of events types](https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads)
in the GitHub documentation.

GitHub will sign all requests hitting the webhook url of your app. This package will automatically verify if the
signature is valid. If it is not, the request was probably not sent by GitHub.

Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200`
will prevent GitHub from resending the same event over and over again. All webhook requests with a valid signature will
be logged in the `github_webhook_calls` table. The table has a `payload` column where the entire payload of the incoming
webhook is saved.

If the signature is not valid, the request will not be logged in the `github_webhook_calls` table but
a `Spatie\GitHubWebhooks\WebhookFailed` exception will be thrown. If something goes wrong during the webhook request the
thrown exception will be saved in the `exception` column. In that case the controller will send a `500` instead of `200`
.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the
events the package will fire.

### Handling webhook requests using jobs

If you want to do something when a specific event type comes in you can define a job that does the work. Here's an
example of such a job:

```php
namespace App\Jobs\GitHubWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

class HandleIssueOpenedWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public GitHubWebhookCall $gitHubWebhookCall;

    public function __construct(
        public GitHubWebhookCall $webhookCall
    ) {}

    public function handle()
    {
        // do your work here

        // you can access the payload of the webhook call with `$this->webhookCall->payload`
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook
requests. This allows you to handle more GitHub webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `github-webhooks.php` config file. The key
should be the name of [the github event type](https://github.com/docs/api#event_types) where but with the `.` replaced
by `_`. The value should be the fully qualified classname.

```php
// config/github-webhooks.php

'jobs' => [
    'issues.opened' => \App\Jobs\GitHubWebhooks\HandleIssueOpenedWebhookJob::class,
],
```

### Working with a `GitHubWebhookCall` model

The `Spatie\GitHubWebhooks\Models\GitHubWebhookCall` model contains some handy methods:

- `headers()`: returns an instance of `Symfony\Component\HttpFoundation\HeaderBag` containing all headers used on the request
- `eventActionName()`: returns the event name and action name of a webhooks, for example `issues.opened`
- `payload($key = null)`: returns the payload of the webhook as an array. Optionally, you can pass a key in the payload which value you needed. For deeply nested values you can use dot notation (example `$githubWebhookCall->payload('issue.user.login');`).

### Handling webhook requests using events

Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this
package will fire. Whenever a valid request hits your app, the package will fire
a `github-webhooks::<name-of-the-event>` event.

The payload of the events will be the instance of `GitHubWebhookCall` that was created for the incoming request.

Let's take a look at how you can listen for such an event. In the `EventServiceProvider` you can register listeners.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'github-webhooks::issues.opened' => [
        App\Listeners\IssueOpened::class,
    ],
];
```

Here's an example of such a listener:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;

class IssueOpened implements ShouldQueue
{
    public function handle(GitHubWebhookCall $webhookCall)
    {
        // do your work here

        // you can access the payload of the webhook call with `$webhookCall->payload`
    }
}
```

We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook
requests. This allows you to handle more GitHub webhook requests and avoid timeouts.

The above example is only one way to handle events in Laravel. To learn the other options,
read [the Laravel documentation on handling events](https://laravel.com/docs/5.5/events).

## Deleting processed webhooks

The `Spatie\GitHubWebhooks\Models\GitHubWebhookCall` is [`MassPrunable`](https://laravel.com/docs/8.x/eloquent#mass-pruning). To delete all processed webhooks every day you can schedule this command.

```php
$schedule->command('model:prune', [
    '--model' => [\Spatie\GitHubWebhooks\Models\GitHubWebhookCall::class],
])->daily();
```

All models that are older than the specified amount of days in the `prune_webhook_calls_after_days` key of the `github-webhooks` config file will be deleted.

## Advanced usage

### Retry handling a webhook

All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while
handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed the cause
of failure, like this:

```php
use Spatie\GitHubWebhooks\Models\GitHubWebhookCall;
use Spatie\GitHubWebhooks\Jobs\ProcessGitHubWebhookJob;

dispatch(new ProcessGitHubWebhookJob(GitHubWebhookCall::find($id)));
```

### Performing custom logic

You can add some custom logic that should be executed before and/or after the scheduling of the queued job by using your
own model. You can do this by specifying your own model in the `model` key of the `github-webhooks` config file. The
class should extend `Spatie\GitHubWebhooks\ProcessGitHubWebhookJob`.

Here's an example:

```php
use Spatie\GitHubWebhooks\Jobs\ProcessGitHubWebhookJob;

class MyCustomGitHubWebhookJob extends ProcessGitHubWebhookJob
{
    public function handle()
    {
        // do some custom stuff beforehand

        parent::handle();

        // do some custom stuff afterwards
    }
}
```

### Determine if a request should be processed

You may use your own logic to determine if a request should be processed or not. You can do this by specifying your own
profile in the `profile` key of the `github-webhooks` config file. The class should
implement `Spatie\WebhookClient\WebhookProfile\WebhookProfile`.

GitHub might occasionally send a webhook
request [more than once](https://github.com/docs/webhooks/best-practices#duplicate-events). In this example we will make
sure to only process a request if it wasn't processed before.

```php
use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class GitHubWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return ! WebhookCall::where('payload->id', $request->get('id'))->exists();
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
