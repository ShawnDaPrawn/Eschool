<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Admissions\Application;
use App\Models\Admissions\IntakeGrade;
use App\Models\Platform\School;
use App\Services\Admissions\AdmissionOffers;
use App\Services\Admissions\IntakeRetention;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaintainAdmissions extends Command
{
    protected $signature = 'admissions:maintain {--retention : Erase due applications and orphan private files instead of recovering offers}';

    protected $description = 'Recover waiting-list offers or erase due private admissions data per explicit school';

    public function handle(): int
    {
        $failed = false;
        School::orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, function () use ($school): void {
                    if ($this->option('retention')) {
                        // Erasure must continue for suspended schools and disabled modules.
                        Application::select(['id'])->orderBy('id')->chunkById(100, function ($rows): void {
                            foreach ($rows as $row) {
                                app(IntakeRetention::class)->purge((int) $row->id);
                            }
                        });
                        app(IntakeRetention::class)->orphanFiles();

                        return;
                    }
                    $gate = app(ModuleGate::class);
                    $gate->forget();
                    if (! $school->isActive() || ! $gate->writable('intake')) {
                        return;
                    }
                    IntakeGrade::select(['id'])->orderBy('id')->chunkById(100, function ($rows): void {
                        foreach ($rows as $row) {
                            app(AdmissionOffers::class)->recover((int) $row->id);
                        }
                    });
                });
            } catch (Throwable $error) {
                $failed = true;
                // Never log applicant content, contact details, resume tokens or file paths.
                Log::warning('Admissions maintenance needs retry.', ['school_id' => (int) $school->id, 'retention' => (bool) $this->option('retention'), 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
