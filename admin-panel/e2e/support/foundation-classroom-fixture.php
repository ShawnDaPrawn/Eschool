<?php

declare(strict_types=1);

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Cards\Card;
use App\Models\Cards\CardPortrait;
use App\Models\Cards\CardScanLog;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\Cards;
use App\Services\Learning\PracticeBank;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

$database = (string) config('database.connections.'.config('database.default').'.database');
abort_unless((app()->environment(['local', 'testing']) && str_ends_with($database, '_test')) || $database === 'eschool_e2e', 403);
$action = getenv('FOUNDATION_FIXTURE_ACTION') ?: 'initial';
if ($action === 'initial') {
    require base_path('e2e/support/mastery-fixture.php');
}

return app(CurrentSchool::class)->run(1, function () use ($action): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    if ($action !== 'initial') {
        $id = (string) getenv('FOUNDATION_FIXTURE_GROUP');
        abort_unless(ctype_digit($id), 422);
        $group = TeachingGroup::where('name', 'like', 'Foundation browser %')->findOrFail((int) $id);
        $cardId = (string) getenv('FOUNDATION_FIXTURE_CARD');
        abort_unless(Str::isUuid($cardId), 422);
        $card = Card::findOrFail($cardId);
        if ($action === 'readonly_inventory') {
            SchoolModule::where('module', 'cards')->update(['read_only' => true]);

            return ['read_only' => true];
        }
        if ($action === 'withdraw') {
            app(Cards::class)->freeze($admin, $card->id, ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic Foundation browser withdrawal']);

            return ['withdrawn' => true];
        }
        abort_unless($action === 'stats', 422);
        $sets = PracticeDailySet::where('teaching_group_id', $group->id)->select('id');
        $entries = PracticeSetEntry::whereIn('practice_daily_set_id', $sets)->select('id');

        return ['reviews' => PracticeReview::whereIn('practice_set_entry_id', $entries)->count(),
            'gate_scans' => CardScanLog::where('card_id', $card->id)->count(), 'tokens' => PersonalAccessToken::count()];
    }
    foreach (['cards', 'daily_practice', 'mastery'] as $module) {
        SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true, 'read_only' => false]);
    }
    app(ModuleGate::class)->forget();
    $teacher = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $teacher->forceFill(['status' => 1, 'password_change_required' => false])->save();
    $base = TeachingGroup::where('name', 'Mastery browser group')->where('session_year_id', schoolSetting('session_year'))->firstOrFail();
    $year = SessionYear::findOrFail($base->session_year_id);
    $member = TeachingGroupMembership::where('teaching_group_id', $base->id)->firstOrFail();
    $student = Students::findOrFail($member->learner_id);
    $student->user->forceFill(['status' => 1, 'password_change_required' => false])->save();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $parent->forceFill(['status' => 1, 'email_verified_at' => now()])->save();
    GuardianLink::updateOrCreate(['learner_id' => $student->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'guardian', 'can_approve' => true]);
    app(ApprovalService::class)->decide($parent, $student, 'approve', config('identity.consent_version'));
    Enrolment::updateOrCreate(['learner_id' => $student->id, 'session_year_id' => $year->id], ['class_section_id' => $base->class_section_id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date]);
    $group = TeachingGroup::create(['name' => 'Foundation browser '.substr((string) Str::uuid(), 0, 8), 'subject_id' => $base->subject_id,
        'grade_level_id' => $base->grade_level_id, 'session_year_id' => $year->id, 'class_section_id' => $base->class_section_id, 'active' => true]);
    TeachingAllocation::create(['teaching_group_id' => $group->id, 'teacher_id' => Teacher::where('user_id', $teacher->id)->value('id'), 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic Foundation teacher allocation']);
    TeachingGroupMembership::create(['teaching_group_id' => $group->id, 'learner_id' => $student->id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'mark_rule' => 'catch_up', 'reason' => 'Synthetic Foundation learner membership']);
    $content = ContentItem::create(['author_id' => $teacher->id, 'subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
        'session_year_id' => $year->id, 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'active' => true]);
    $source = ContentSource::create(['content_item_id' => $content->id, 'uploaded_by' => $teacher->id, 'name' => 'Synthetic Foundation fractions.md',
        'path' => 'synthetic/foundation-no-file.md', 'mime' => 'text/markdown', 'extension' => 'md', 'bytes' => 1, 'sha256' => str_repeat('b', 64), 'scan_state' => 'clean']);
    $version = ContentVersion::create(['content_item_id' => $content->id, 'content_source_id' => $source->id, 'created_by' => $teacher->id, 'number' => 1,
        'state' => 'published', 'metadata' => ['group_ids' => [$group->id], 'title' => 'Synthetic Foundation fractions'],
        'markdown' => '# Synthetic Foundation fractions', 'generation_id' => (string) Str::uuid(), 'published_at' => now()]);
    $content->update(['published_version_id' => $version->id]);
    ContentItemGroup::create(['content_item_id' => $content->id, 'teaching_group_id' => $group->id]);
    $topic = MasteryTopic::where('subject_id', $base->subject_id)->where('name', 'Mastery browser fractions')->firstOrFail();
    app(PracticeBank::class)->approve($teacher, (int) $group->id, null, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
        'topic_id' => (int) $topic->id, 'content_version_id' => (int) $version->id, 'estimated_seconds' => 60, 'active' => true,
        'definition' => ['kind' => 'number', 'prompt' => 'Synthetic Foundation one half as a decimal?', 'explanation' => 'Divide one by two to get 0.5.', 'options' => [], 'answers' => ['0.5']]]);
    Card::where('user_id', $student->user_id)->whereNotNull('active_identity')->update(['status' => 'replaced', 'active_identity' => null]);
    if (! CardPortrait::where('user_id', $student->user_id)->exists()) {
        app()->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-foundation-browser', 'signature' => null];
            }
        });
        app(CardPortraits::class)->upload($admin, (int) $student->user_id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('synthetic-foundation.jpg', 120, 150));
    }
    $card = app(Cards::class)->issue($admin, ['operation_id' => (string) Str::uuid(), 'person_id' => (int) $student->user_id, 'type' => 'learner']);

    return ['group' => (int) $group->id, 'group_name' => $group->name, 'learner_name' => $student->user->first_name,
        'card' => $card['id'], 'locator' => 'NELOC1.1.'.$card['id'], 'tokens' => PersonalAccessToken::count()];
});
