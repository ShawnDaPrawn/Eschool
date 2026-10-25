<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Services\Platform\Modules\ModuleGate;

class AdmissionAllocation
{
    public function choices(Application $application, string $startsOn): array
    {
        $intake = Intake::findOrFail($application->intake_id);
        $grade = IntakeGrade::where('intake_id', $intake->id)->findOrFail($application->intake_grade_id);
        $rooms = ClassSection::with(['class', 'section'])->whereHas('class', fn ($q) => $q->where('grade_level_id', $grade->grade_level_id))->orderBy('id')->get();
        $counts = Enrolment::where('session_year_id', $intake->session_year_id)->onDate($startsOn)->selectRaw('class_section_id, COUNT(*) AS total')->groupBy('class_section_id')->pluck('total', 'class_section_id');
        $homerooms = $rooms->map(fn ($room) => ['id' => (int) $room->id, 'name' => $room->class->name.' '.$room->section->name, 'learners' => (int) ($counts[$room->id] ?? 0)])->sortBy(fn ($r) => [$r['learners'], $r['id']])->values()->all();
        $houses = [];
        app(ModuleGate::class)->forget();
        if (app(ModuleGate::class)->writable('house_points')) {
            $houseCounts = HouseMembership::where('session_year_id', $intake->session_year_id)->where('active', true)->whereDate('starts_on', '<=', $startsOn)
                ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $startsOn))->selectRaw('house_id, COUNT(*) AS total')->groupBy('house_id')->pluck('total', 'house_id');
            $houses = House::where('active', true)->orderBy('id')->get()->map(fn ($house) => ['id' => (int) $house->id, 'name' => $house->name, 'learners' => (int) ($houseCounts[$house->id] ?? 0)])
                ->sortBy(fn ($row) => [$row['learners'], $row['id']])->values()->all();
        }

        return ['homerooms' => $homerooms, 'houses' => $houses, 'proposed_homeroom_id' => $homerooms[0]['id'] ?? null, 'proposed_house_id' => $houses[0]['id'] ?? null];
    }
}
