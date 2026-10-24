<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\Evaluation;
use Illuminate\Console\Command;
use Throwable;

class EvaluateAi extends Command
{
    protected $signature = 'ai:eval {feature} {--observations= : Private JSONL with recorded outputs and adjudicated labels}';

    protected $description = 'Score recorded AI observations; does not call or certify a live provider';

    public function handle(Evaluation $evaluation): int
    {
        $path = $this->option('observations');
        if (! is_string($path) || $path === '') {
            $this->error('Recorded observations are required; no inference provider is activated by this command.');

            return self::FAILURE;
        }
        try {
            $result = $evaluation->score((string) $this->argument('feature'), $path);
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));

            return $result['passed'] ? self::SUCCESS : self::FAILURE;
        } catch (Throwable) {
            $this->error('Evaluation refused: check the bounded dataset, unique case IDs, labels and configured thresholds.');

            return self::FAILURE;
        }
    }
}
