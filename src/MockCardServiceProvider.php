<?php

/**
 * Registers the MockCard client and chaos runner in the container, the `mockcard.probe` middleware
 * alias and the `mockcard:chaos` Artisan command.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Laravel;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use MockCard\ChaosRunner;
use MockCard\Client;
use MockCard\Laravel\Console\ChaosCommand;
use MockCard\Laravel\Http\SkipMockCardProbes;

final class MockCardServiceProvider extends ServiceProvider
{
    /**
     * Binds the client and runner.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mockcard.php', 'mockcard');
        $this->app->singleton(Client::class, fn ($app) => new Client($app['config']['mockcard.api_key'], $app['config']['mockcard.base_url']));
        $this->app->singleton(ChaosRunner::class, fn ($app) => new ChaosRunner($app->make(Client::class)));
    }

    /**
     * Publishes config, aliases the middleware and registers the command.
     *
     * @param Router $router The router.
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->publishes([__DIR__ . '/../config/mockcard.php' => config_path('mockcard.php')], 'mockcard-config');
        $router->aliasMiddleware('mockcard.probe', SkipMockCardProbes::class);
        if ($this->app->runningInConsole()) {
            $this->commands([ChaosCommand::class]);
        }
    }
}
