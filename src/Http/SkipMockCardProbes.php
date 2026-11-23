<?php

/**
 * Middleware for payment webhook routes: answers verified MockCard production probes with
 * 200 + `MockCard-Probe-Ack: skipped` before your controller runs. Forged probes pass through to your
 * normal handling, so the header can never make your app process anything it otherwise wouldn't.
 *
 *   Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('mockcard.probe');
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Laravel\Http;

use Closure;
use Illuminate\Http\Request;
use MockCard\Probe;
use Symfony\Component\HttpFoundation\Response;

final class SkipMockCardProbes
{
    /**
     * Handles the request.
     *
     * @param Request $request The incoming request.
     * @param Closure $next    The rest of the pipeline.
     * @return Response The probe acknowledgement, or your handler's response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headers = array_map(static fn ($v) => $v[0] ?? '', $request->headers->all());
        if (Probe::isProbe($headers) && Probe::verify($headers, $request->getContent(), (string) config('mockcard.probe_secret'))) {
            return response()->json(['received' => true, 'probe' => true])->header(Probe::ACK_HEADER, Probe::ACK_VALUE);
        }
        return $next($request);
    }
}
