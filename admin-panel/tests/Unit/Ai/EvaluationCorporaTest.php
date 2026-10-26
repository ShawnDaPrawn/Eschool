<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Console\Commands\EvaluateAi;
use App\Services\Ai\Evaluation;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/** Synthetic scorer fixtures exercise software; they never qualify a model. */
class EvaluationCorporaTest extends TestCase
{
    private Application $application;

    private Container $previousContainer;

    private mixed $previousFacades;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacades = Facade::getFacadeApplication();
        $this->application = new Application(dirname(__DIR__, 3));
        $this->application->instance('config', new Repository(['ai' => require dirname(__DIR__, 3).'/config/ai.php']));
        $http = new Factory;
        $http->preventStrayRequests();
        $this->application->instance(Factory::class, $http);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->application);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacades);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    /** Purposes are discovered, so a newly registered purpose needs its corpus in CI. */
    public static function corpora(): array
    {
        $config = require dirname(__DIR__, 3).'/config/ai.php';
        $features = array_keys($config['inference']['feature_policies']);
        $features[] = 'redteam';

        return array_combine($features, array_map(fn ($feature) => [$feature], $features));
    }

    public static function regressions(): array
    {
        $cases = [];
        foreach (self::corpora() as [$feature]) {
            foreach (['refusal', 'subject_lock', 'safety', 'leakage'] as $metric) {
                $cases[$feature.' '.$metric] = [$feature, $metric];
            }
        }

        return $cases;
    }

    private function cases(string $feature): array
    {
        $path = resource_path('ai/evals/'.$feature.'.jsonl');
        $this->assertFileExists($path);
        $this->assertLessThanOrEqual(1048576, filesize($path));
        $rows = array_map(fn ($line) => json_decode($line, true, 16, JSON_THROW_ON_ERROR), file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $this->assertNotEmpty($rows);
        $this->assertLessThanOrEqual(1000, count($rows));

        return $rows;
    }

    /** These are deliberately invented test labels, never saved as provider evidence. */
    private function synthetic(array $cases): array
    {
        return array_map(fn ($case) => ['id' => $case['id'], 'refused' => $case['refused'], 'safe' => true,
            'subject' => $case['subject'], 'output' => 'Synthetic scorer fixture only. No provider was called.',
            'provenance' => 'synthetic-scorer-fixture'], $cases);
    }

    private function write(array $rows): string
    {
        $file = tempnam(sys_get_temp_dir(), 'synthetic-eval-');
        $this->assertIsString($file);
        $this->files[] = $file;
        file_put_contents($file, implode("\n", array_map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), $rows))."\n");

        return $file;
    }

    private function command(string $feature, string $path): CommandTester
    {
        $command = new EvaluateAi;
        $command->setLaravel($this->application);
        $tester = new CommandTester($command);
        $tester->execute(['feature' => $feature, '--observations' => $path]);

        return $tester;
    }

    #[DataProvider('corpora')]
    public function test_each_registered_purpose_has_explicit_bounded_cases_and_controls(string $feature): void
    {
        $cases = $this->cases($feature);
        $ids = [];
        foreach ($cases as $case) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_-]{1,64}$/D', $case['id']);
            $this->assertArrayNotHasKey($case['id'], $ids);
            $ids[$case['id']] = true;
            $this->assertIsBool($case['refused']);
            $this->assertNotEmpty($case['subject']);
            $this->assertNotEmpty($case['input']);
            $this->assertLessThanOrEqual(4000, mb_strlen($case['input']));
            $this->assertNotEmpty($case['forbidden']);
            foreach ($case['forbidden'] as $canary) {
                $this->assertIsString($canary);
                $this->assertNotSame('', $canary);
            }
        }
        $this->assertContains(true, array_column($cases, 'refused'));
        $this->assertContains(false, array_column($cases, 'refused'));
    }

    #[DataProvider('corpora')]
    public function test_synthetic_clean_observations_exercise_each_corpus_and_cli_without_network(string $feature): void
    {
        $cases = $this->cases($feature);
        $path = $this->write($this->synthetic($cases));
        $result = (new Evaluation)->score($feature, $path);
        $this->assertTrue($result['passed']);
        $this->assertSame(count($cases), $result['count']);
        foreach ($result['successful_checks'] as $count) {
            $this->assertSame(count($cases), $count);
        }
        $command = $this->command($feature, $path);
        $this->assertSame(0, $command->getStatusCode());
        $this->assertTrue(json_decode(trim($command->getDisplay()), true, 16, JSON_THROW_ON_ERROR)['passed']);
        $this->application->make(Factory::class)->assertNothingSent();
        $this->assertFalse(config('ai.inference.enabled'));
        foreach (config('ai.providers') as $provider) {
            $this->assertFalse($provider['qualified']);
        }
    }

    #[DataProvider('regressions')]
    public function test_each_metric_regression_returns_failed_score_and_nonzero_cli_status(string $feature, string $metric): void
    {
        $cases = $this->cases($feature);
        $rows = $this->synthetic($cases);
        match ($metric) {
            'refusal' => $rows[0]['refused'] = ! $rows[0]['refused'],
            'subject_lock' => $rows[0]['subject'] = 'synthetic-wrong-subject',
            'safety' => $rows[0]['safe'] = false,
            'leakage' => $rows[0]['output'] = 'Synthetic forbidden canary: '.$cases[0]['forbidden'][0],
        };
        $path = $this->write($rows);
        $result = (new Evaluation)->score($feature, $path);
        $this->assertFalse($result['passed']);
        $this->assertSame(count($cases) - 1, $result['successful_checks'][$metric]);
        $this->assertSame(1, $this->command($feature, $path)->getStatusCode());
        $this->application->make(Factory::class)->assertNothingSent();
    }

    public function test_shared_redteam_covers_required_attack_classes_and_only_registered_purposes(): void
    {
        $cases = $this->cases('redteam');
        foreach (['content_injection', 'pixel_injection', 'jailbreak', 'pii_extraction', 'self_harm', 'abuse', 'cross_learner'] as $category) {
            $this->assertContains($category, array_column($cases, 'category'));
        }
        foreach ($cases as $case) {
            $this->assertArrayHasKey($case['feature'], config('ai.inference.feature_policies'));
        }
    }
}
