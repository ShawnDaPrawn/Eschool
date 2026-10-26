<?php

declare(strict_types=1);

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Peer tutoring browser fixtures require an isolated test database.');
}
require base_path('e2e/support/mastery-fixture.php');

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $teacher = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('peer_tutoring', true, $admin);
    $parent->forceFill(['email_verified_at' => now()])->save();
    $group = TeachingGroup::where('name', 'Mastery browser group')->firstOrFail();
    $year = SessionYear::findOrFail($group->session_year_id);
    $tutee = Students::findOrFail(TeachingGroupMembership::where('teaching_group_id', $group->id)->value('learner_id'));
    $tutor = Students::where('id', '!=', $tutee->id)->where('class_section_id', $tutee->class_section_id)->firstOrFail();
    foreach ([$tutee, $tutor] as $student) {
        $student->user->forceFill(['password_change_required' => false])->save();
        Enrolment::updateOrCreate(['learner_id' => $student->id, 'session_year_id' => $year->id],
            ['class_section_id' => $student->class_section_id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date]);
        TeachingGroupMembership::firstOrCreate(['teaching_group_id' => $group->id, 'learner_id' => $student->id],
            ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'mark_rule' => 'catch_up']);
        GuardianLink::updateOrCreate(['learner_id' => $student->id, 'guardian_user_id' => $parent->id],
            ['status' => 'active', 'relationship' => 'guardian', 'can_approve' => true]);
        app(ApprovalService::class)->decide($parent, $student, 'approve', config('identity.consent_version'));
    }
    $topics = MasteryTopic::where('subject_id', $group->subject_id)->get();
    foreach ($topics as $topic) {
        MasteryRecord::updateOrCreate(['subject_id' => $group->subject_id, 'learner_id' => $tutor->id, 'topic_id' => $topic->id, 'audience' => 'staff'],
            ['level' => 4, 'score_bp' => 10000, 'confidence_bp' => 6000, 'evidence_count' => 2, 'computed_at' => now()]);
    }
    $topic = $topics->firstWhere('name', 'Mastery browser fractions');
    $clock = CarbonImmutable::instance(now())->setTimezone(app(CurrentSchool::class)->school()->timezone)->startOfMinute();
    try {
        CarbonImmutable::setTestNow($clock->subHours(3));
        Carbon::setTestNow($clock->subHours(3));
        $row = app(PeerSessions::class)->create($teacher, ['operation_id' => (string) Str::uuid(), 'teaching_group_id' => (int) $group->id,
            'tutor_learner_id' => (int) $tutor->id, 'tutee_learner_id' => (int) $tutee->id, 'topic_id' => (int) $topic->id,
            'venue' => 'Synthetic completed-time browser supervision', 'starts_at' => $clock->subHours(2)->format('Y-m-d\TH:i'), 'ends_at' => $clock->subHour()->format('Y-m-d\TH:i')]);
    } finally {
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }

    return ['group' => (int) $group->id, 'tutee' => (int) $tutee->id, 'topic' => (int) $topic->id, 'prepared' => $row->id,
        'starts' => $clock->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'), 'ends' => $clock->addDay()->setTime(15, 0)->format('Y-m-d\TH:i')];
});
