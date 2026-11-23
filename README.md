# mockcard/laravel

MockCard for Laravel: guard your payment webhook routes against MockCard's production probes, and run
payment chaos scenarios from Artisan or your Pest/PHPUnit suite. Built on `mockcard/mockcard-php`.

Author: Arixeva Labs <hello@arixevalabs.com> · [mockcard.io](https://www.mockcard.io) · MIT

```sh
composer require mockcard/laravel
```

`.env`: `MOCKCARD_API_KEY`, `MOCKCARD_PROJECT_ID`, `MOCKCARD_PROBE_SECRET` (from the MockCard dashboard).

## Probe guard

```php
Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('mockcard.probe');
```

Verified MockCard probes get `200` + `MockCard-Probe-Ack: skipped`; your controller never sees them.
Works with Laravel Cashier's webhook route too.

## CI: Artisan

```yaml
- run: php artisan serve --port=8000 &
- run: php artisan mockcard:chaos http://127.0.0.1:8000/webhooks/stripe --min-score=100
```

## Pest / PHPUnit, with outcome assertions

```php
use MockCard\ChaosRunner;
use MockCard\Testing\AssertsChaosRun;

uses(AssertsChaosRun::class);

it('survives payment chaos', function () {
    $run = app(ChaosRunner::class)->run(
        config('mockcard.project_id'),
        'http://127.0.0.1:8000/webhooks/stripe',
        assertions: ['duplicate' => ['order fulfilled once' => fn () => Order::where('status', 'paid')->count() === 1]],
    );
    $this->assertChaosRunPassed($run);
});
```

The `MockCard` facade exposes the API client: `MockCard::listProjects()`, `MockCard::createRun([...])`.
