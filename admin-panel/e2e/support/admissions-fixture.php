<?php

declare(strict_types=1);

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\IntakeGrade;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Admissions\IntakeCatalogue;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$safe = require base_path('e2e/support/admissions-environment.php');
abort_unless($safe(app()->environment(), (string) config('database.connections.'.config('database.default').'.database'), filter_var(getenv('CI'), FILTER_VALIDATE_BOOLEAN)), 403);

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('intake', true, $admin);
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $grade = (int) GradeLevel::where('code', '5')->value('id');
    ClassSection::with('class')->whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->firstOrFail()->class->forceFill(['grade_level_id' => $grade])->save();
    $fields = ProfileFieldDefinition::where('profile_type', 'learner')->whereIn('source', ['user.first_name', 'user.last_name', 'user.dob'])->pluck('id', 'source')->all();
    $row = app(IntakeCatalogue::class)->save($admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic browser intake '.Str::uuid(), 'session_year_id' => $year->id, 'state' => 'open',
        'opens_at' => now()->subDay()->format('Y-m-d\TH:i'), 'closes_at' => now()->addDays(14)->format('Y-m-d\TH:i'), 'application_fee_cents' => 2500, 'keep_for_days' => 30,
        'field_ids' => array_values($fields), 'required_documents' => [], 'grades' => [['grade_level_id' => $grade, 'places' => 1]]]);

    return ['id' => (int) $row->id, 'public_id' => $row->public_id, 'path' => '/apply/'.app(CurrentSchool::class)->school()->slug.'/'.$row->public_id, 'fields' => $fields, 'grade' => (int) IntakeGrade::where('intake_id', $row->id)->value('id'),
        'category' => (int) Category::where('status', 1)->value('id'), 'starts_on' => min(now()->toDateString(), substr((string) $year->end_date, 0, 10))];
});
