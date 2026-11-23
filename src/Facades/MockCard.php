<?php

/**
 * `MockCard::listProjects()`, `MockCard::createRun([...])` and the rest of the client, as a facade.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MockCard\Client;

final class MockCard extends Facade
{
    /**
     * The container binding behind the facade.
     *
     * @return string The client class.
     */
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
