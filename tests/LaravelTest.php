<?php

/**
 * Tests for the Laravel package with Orchestra Testbench: the probe middleware on a real route (using
 * the shared conformance fixtures), the container bindings and the facade.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Laravel\Tests;

use Illuminate\Support\Facades\Route;
use MockCard\ChaosRunner;
use MockCard\Client;
use MockCard\Laravel\Facades\MockCard;
use MockCard\Laravel\MockCardServiceProvider;
use Orchestra\Testbench\TestCase;

final class LaravelTest extends TestCase
{
    private array $fixture;
    private int $handled = 0;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string> The package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [MockCardServiceProvider::class];
    }

    /**
     * Configures the package and registers a guarded webhook route.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $this->fixture = json_decode((string) file_get_contents(__DIR__ . '/../../conformance/probe-signatures.json'), true);
        $app['config']->set('mockcard.api_key', 'mc_test');
        $app['config']->set('mockcard.probe_secret', $this->fixture['secret']);
    }

    /**
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    protected function defineRoutes($router): void
    {
        $router->post('/webhooks/stripe', function () {
            $this->handled++;
            return response()->json(['processed' => true]);
        })->middleware('mockcard.probe');
    }

    /**
     * Sends a POST with a raw body and headers to the guarded route.
     *
     * @param array  $headers Request headers.
     * @param string $body    Raw body.
     * @return \Illuminate\Testing\TestResponse
     */
    private function postRaw(array $headers, string $body)
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $k => $v) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }
        return $this->call('POST', '/webhooks/stripe', [], [], [], $server, $body);
    }

    public function testVerifiedProbeIsAcknowledgedWithoutReachingTheController(): void
    {
        $body = $this->fixture['cases'][0]['body'];
        $t = time();
        $signature = "t=$t,v1=" . hash_hmac('sha256', "$t.$body", $this->fixture['secret']);
        $this->postRaw(['MockCard-Probe' => '1', 'MockCard-Signature' => $signature], $body)->assertOk()->assertHeader('MockCard-Probe-Ack', 'skipped');
        self::assertSame(0, $this->handled);
    }

    public function testForgedProbesAndNormalEventsReachTheController(): void
    {
        $tooOld = $this->fixture['cases'][2];
        $this->postRaw($tooOld['headers'], $tooOld['body'])->assertOk()->assertHeaderMissing('MockCard-Probe-Ack');
        $this->postRaw([], '{"type":"payment_intent.succeeded"}')->assertOk();
        self::assertSame(2, $this->handled);
    }

    public function testContainerAndFacadeAreWired(): void
    {
        self::assertInstanceOf(ChaosRunner::class, $this->app->make(ChaosRunner::class));
        self::assertSame($this->app->make(Client::class), MockCard::getFacadeRoot());
        self::assertArrayHasKey('mockcard:chaos', \Illuminate\Support\Facades\Artisan::all());
    }
}
