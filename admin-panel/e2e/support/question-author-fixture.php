<?php

declare(strict_types=1);
use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Question author browser fixtures require an isolated test database.');
}
$run = getenv('QUESTION_AUTHOR_FIXTURE_RUN') ?: bin2hex(random_bytes(8));
if (! preg_match('/^[a-f0-9]{16}$/', $run)) {
    throw new RuntimeException('Use a generated question author fixture run identifier.');
}
// A fresh namespace and transaction prevent a failed first setup from being
// mistaken for a complete group when Playwright retries this file.
$names = [
    'subject' => 'Question author rehearsal '.$run, 'code' => 'QUESTION-UI', 'group' => 'Question author group '.$run,
    'topics' => ['Question author foundation', 'Question author fractions', 'Question author decimals'],
];
echo DB::transaction(function () use ($names): int {
    $masteryBrowserFixture = $names;
    require base_path('e2e/support/mastery-fixture.php');

    return app(CurrentSchool::class)->run(1, function () use ($names): int {
        SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $actor = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
        $group = TeachingGroup::where('name', $names['group'])->firstOrFail();
        $content = ContentItem::where('subject_id', $group->subject_id)->where('author_id', $actor->id)->first();
        if (! $content) {
            $content = ContentItem::create(['author_id' => $actor->id, 'subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
                'session_year_id' => $group->session_year_id, 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'active' => true]);
            $source = ContentSource::create(['content_item_id' => $content->id, 'uploaded_by' => $actor->id, 'name' => 'Synthetic question author.md', 'path' => 'synthetic/no-file.md',
                'mime' => 'text/markdown', 'extension' => 'md', 'bytes' => 1, 'sha256' => str_repeat('b', 64), 'scan_state' => 'clean']);
            $version = ContentVersion::create(['content_item_id' => $content->id, 'content_source_id' => $source->id, 'created_by' => $actor->id, 'number' => 1,
                'state' => 'published', 'metadata' => ['group_ids' => [$group->id], 'title' => 'Question author lesson'], 'markdown' => '# Fractions', 'generation_id' => (string) Str::uuid(), 'published_at' => now()]);
            $content->update(['published_version_id' => $version->id]);
            ContentItemGroup::create(['content_item_id' => $content->id, 'teaching_group_id' => $group->id]);
        }

        return (int) $group->id;
    });
});
