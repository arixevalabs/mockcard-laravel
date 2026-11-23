<?php

/**
 * `php artisan mockcard:chaos {target}` — runs MockCard's chaos scenarios against your webhook route
 * (e.g. in CI after `php artisan serve &`) and exits non-zero when the run fails.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Laravel\Console;

use Illuminate\Console\Command;
use MockCard\ChaosRunner;
use MockCard\MockCardException;

final class ChaosCommand extends Command
{
    protected $signature = 'mockcard:chaos
        {target : Your webhook URL, e.g. http://127.0.0.1:8000/webhooks/stripe}
        {--project= : Project ID (defaults to config mockcard.project_id)}
        {--scenarios= : Comma-separated scenarios}
        {--min-score=100 : Fail below this score}
        {--time-scale=0.2 : 0–1, shrinks waits}';

    protected $description = 'Run MockCard payment chaos scenarios against your webhook route';

    /**
     * Runs the command.
     *
     * @param ChaosRunner $runner The chaos runner.
     * @return int Exit code: 0 when the score meets --min-score, 1 otherwise.
     */
    public function handle(ChaosRunner $runner): int
    {
        try {
            $scenarios = array_filter(array_map('trim', explode(',', (string) $this->option('scenarios'))));
            $run = $runner->run(
                (string) ($this->option('project') ?: config('mockcard.project_id')),
                (string) $this->argument('target'),
                $scenarios,
                [],
                (float) $this->option('time-scale'),
            );
        } catch (MockCardException $e) {
            $this->error("MockCard: {$e->errorCode}: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->table(['', 'Scenario', 'Check', 'Detail'], array_map(
            static fn (array $c) => [$c['passed'] ? '✓' : '✗', $c['scenario'], $c['name'], $c['detail']],
            $run['results']['checks'] ?? [],
        ));
        $this->line("Run {$run['id']}: {$run['score']}/100 ({$run['status']}) — https://www.mockcard.io/dashboard/runs/{$run['id']}");
        return ($run['score'] ?? 0) >= (int) $this->option('min-score') ? self::SUCCESS : self::FAILURE;
    }
}
