<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Academics\SubjectStandards;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use JsonException;

class ImportSubjectStandards extends Command
{
    protected $signature = 'academics:import-subject-standards {file : Reviewed JSON catalogue} {--publish : Make the reviewed revisions available to schools}';

    protected $description = 'Import immutable, sourced CAPS/IEB reference revisions as a trusted platform operator';

    public function handle(SubjectStandards $standards): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file) || ! is_readable($file) || filesize($file) > 8 * 1024 * 1024) {
            $this->error('Choose a readable local catalogue smaller than 8 MiB.');

            return self::FAILURE;
        }
        try {
            $data = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ! array_is_list($data)) {
                $this->error('The catalogue must be a JSON list of standards.');

                return self::FAILURE;
            }
            $this->line(json_encode($standards->import($data, (bool) $this->option('publish')), JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->error($e->validator->errors()->first());
        } catch (JsonException) {
            $this->error('The catalogue is not valid bounded JSON.');
        }

        return self::FAILURE;
    }
}
