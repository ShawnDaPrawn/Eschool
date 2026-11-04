<?php

declare(strict_types=1);

namespace Tests\Feature\Reading;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\Reading\ReadingAnswer;
use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingBookText;
use App\Models\Reading\ReadingHiddenItem;
use App\Models\Reading\ReadingProgress;
use App\Models\Reading\ReadingQuestion;
use App\Models\Reading\ReadingShelf;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\RecognitionBadge;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Reading\ReadingCatalogue;
use App\Services\Reading\ReadingFetcher;
use App\Services\Reading\ReadingSchool;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * The reading library (Action Plan 4.4): HQ builds the catalogue, a school
 * hides shelves and books, and a learner reads cleared public-domain books
 * fetched once from the mirror. engineering/specs/learning/reading-library.md.
 * DemoSchool: Asha and Ben are in Grade 5 A, Cara in Grade 6 C; every School B
 * name carries "Baobab".
 */
class ReadingLibraryTest extends TestCase
{
    use PassesPlatformTwoFactor, UsesTwoSchools, ValidatesOpenApiResponses;

    private const MIRROR = 'https://mirror.example.org';

    private array $staff = [];

    /** What the faked mirror answers next (a closure, so a test can change it). */
    private $mirrorAnswer = null;

    protected function setUp(): void
    {
        parent::setUp();
        // A Monday; fixtures and requests share the clock.
        $this->travelTo(CarbonImmutable::parse('2026-11-02 08:00:00', 'Africa/Johannesburg'));
        $this->setUpTwoSchools();
        Storage::fake(ReadingFetcher::DISK);
        config(['reading.mirror_url' => self::MIRROR]);
        $this->mirrorAnswer = fn () => Http::response('Not found', 404, ['Content-Type' => 'text/plain']);
        Http::fake([self::MIRROR.'/*' => fn () => ($this->mirrorAnswer)()]);
        $grade5 = (int) GradeLevel::query()->where('code', '5')->value('id');
        $grade6 = (int) GradeLevel::query()->where('code', '6')->value('id');
        foreach ([1 => $this->schoolA, self::SCHOOL_B_ID => $this->schoolB] as $id => $school) {
            app(CurrentSchool::class)->run($id, function () use ($school, $grade5, $grade6) {
                SchoolModule::query()->updateOrCreate(['module' => 'reading'], ['enabled' => true, 'in_package' => true]);
                SchoolModule::query()->updateOrCreate(['module' => 'awards'], ['enabled' => true, 'in_package' => true]);
                ClassSchool::query()->whereKey($school->ids['class_a'])->update(['grade_level_id' => $grade5]);
                ClassSchool::query()->whereKey($school->ids['class_c'])->update(['grade_level_id' => $grade6]);
                SessionYear::query()->whereKey($school->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
                $this->approve($school);
            });
        }
        app(ModuleGate::class)->forget();
        app(CurrentSchool::class)->forget();
    }

    /** A learner's account works only once a guardian has approved it. */
    private function approve(DemoSchool $school): void
    {
        foreach (['asha' => 'farid', 'ben' => 'gina', 'cara' => 'farid'] as $learner => $guardian) {
            User::findOrFail($school->ids[$learner.'_user'])->forceFill(['password_change_required' => false])->save();
            User::findOrFail($school->ids[$guardian.'_user'])->forceFill(['email_verified_at' => now(), 'status' => 1])->save();
            GuardianLink::updateOrCreate(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user']],
                ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
            LearnerApproval::create(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user'],
                'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        }
    }

    private function hqStaff(string $role = 'content'): User
    {
        return $this->staff[$role] ??= app(CurrentSchool::class)->acrossSchools(function () use ($role) {
            $user = User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Reader', 'email' => $role.'@reading.example', 'mobile' => '',
                'password' => 'Synthetic-reading-password-417!', 'status' => 1]);
            PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
            PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHours(4)]);

            return $user;
        });
    }

    private function schoolBAdmin(): User
    {
        return app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): User {
            $user = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal.baobab@reading.example', 'mobile' => '', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $user->assignRole('Super Admin');
            app(MembershipService::class)->grant($user, self::SCHOOL_B_ID, 'Super Admin');

            return $user;
        });
    }

    private function asHq(callable $callback): mixed
    {
        return app(CurrentSchool::class)->acrossSchools($callback);
    }

    /** A cleared book on a Grade 4 to 6 shelf, built through the catalogue service as HQ. */
    private function catalogue(array $book = [], bool $clear = true): array
    {
        return $this->asHq(function () use ($book, $clear) {
            $catalogue = app(ReadingCatalogue::class);
            $staff = $this->hqStaff();
            $row = $catalogue->createBook($staff, $book + ['gutenberg_id' => 90001, 'title' => 'The Synthetic Garden', 'author' => 'Ada Example', 'author_birth_year' => 1850,
                'author_death_year' => 1900, 'language' => 'en', 'subjects' => "Gardens\nFriendship", 'reading_level' => 'developing']);
            if ($clear) {
                $catalogue->decideRights($staff, $row->id, ['decision' => ReadingBook::CLEARED, 'basis' => 'Author died in 1900; checked the edition has no translator or illustrator.',
                    'translator_checked' => true, 'revision' => $row->fresh()->revision]);
            }
            $shelf = ReadingShelf::query()->first() ?? $catalogue->createShelf($staff, ['name' => 'Stories for Grades 4 to 6', 'grade_from' => 4, 'grade_to' => 6]);
            $ids = DB::table('reading_shelf_books')->where('reading_shelf_id', $shelf->id)->orderBy('position')->pluck('reading_book_id')->all();
            $catalogue->setShelfBooks($staff, $shelf->id, ['book_ids' => [...$ids, $row->id], 'revision' => $shelf->fresh()->revision]);

            return ['book' => $row->fresh(), 'shelf' => $shelf->fresh()];
        });
    }

    /** A Project Gutenberg plain-text file: header, the start and end lines, a body of $paragraphs paragraphs, and the licence. */
    private function gutenbergFile(int $paragraphs = 12, string $title = 'THE SYNTHETIC GARDEN'): string
    {
        $body = [];
        foreach (range(1, $paragraphs) as $n) {
            $body[] = "Paragraph $n. The children walked through the garden and counted the flowers, one by one, until the sun went down behind the hills. ".str_repeat('They laughed and talked about the day. ', 8);
        }

        return "\u{FEFF}The Project Gutenberg eBook of The Synthetic Garden\r\n\r\nTitle: The Synthetic Garden\r\nAuthor: Ada Example\r\nTranslator: none\r\n\r\n"
            ."*** START OF THE PROJECT GUTENBERG EBOOK $title ***\r\n\r\nProduced by volunteers of Project Gutenberg\r\n\r\n"
            .implode("\r\n\r\n", $body)
            ."\r\n\r\n*** END OF THE PROJECT GUTENBERG EBOOK $title ***\r\n\r\nSection 1. General Terms of Use and Redistributing Project Gutenberg-tm electronic works\r\n";
    }

    private function fakeMirror(?string $body = null, int $status = 200, string $type = 'text/plain; charset=utf-8', array $headers = []): void
    {
        $body ??= $this->gutenbergFile();
        $this->mirrorAnswer = fn () => Http::response($body, $status, ['Content-Type' => $type] + $headers);
    }

    /** A school account in a fresh browser, after API calls made with a token. */
    private function web(User $user): static
    {
        $this->flushHeaders();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        app(CurrentSchool::class)->forget();

        return $this->actingAs($user, 'web');
    }

    private function as(string $who, ?DemoSchool $school = null, int $schoolId = 1): static
    {
        $school ??= $this->schoolA;
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        app(CurrentSchool::class)->forget();

        return $this->withToken($school->tokens[$who])->withHeaders(['X-School-Id' => (string) $schoolId]);
    }

    private function mirrorCalls(): int
    {
        return count(Http::recorded(fn ($request) => str_starts_with($request->url(), self::MIRROR)));
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected a refusal.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_hq_builds_the_catalogue_and_a_learner_reads_a_cleared_book_fetched_once(): void
    {
        $this->fakeMirror();
        $this->signInAs($this->hqStaff('content'));
        $this->get('/saas/reading')->assertOk()->assertSee('Reading library');
        $this->post('/saas/reading/books', ['gutenberg_id' => 90001, 'title' => 'The Synthetic Garden', 'author' => 'Ada Example', 'author_birth_year' => 1850,
            'author_death_year' => 1900, 'language' => 'en', 'subjects' => "Gardens\nFriendship", 'reading_level' => 'developing', 'summary' => 'Children count flowers.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $book = ReadingBook::query()->sole();
        $this->assertSame('undecided', $book->rights_status);
        $this->assertSame(['Gardens', 'Friendship'], $book->subjects);

        // HQ fetches the text to read the original header before deciding: fetching is not serving.
        $this->post('/saas/reading/books/'.$book->id.'/fetch')->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/saas/reading/books/'.$book->id)->assertOk()->assertSee('Translator: none')->assertSee('Paragraph 1.')->assertSee('Record a rights decision');

        // A positive decision needs the translator checked.
        $this->post('/saas/reading/books/'.$book->id.'/rights', ['decision' => ReadingBook::CLEARED, 'basis' => 'Author died in 1900, edition checked.', 'translator_checked' => '0',
            'revision' => $book->fresh()->revision])->assertSessionHasErrors('translator_checked');
        $this->post('/saas/reading/books/'.$book->id.'/rights', ['decision' => ReadingBook::CLEARED, 'basis' => 'Author died in 1900, edition checked.', 'translator_checked' => '1',
            'revision' => $book->fresh()->revision])->assertRedirect()->assertSessionHasNoErrors();
        $book->refresh();
        $this->assertTrue($book->cleared());
        $decision = DB::table('reading_rights_decisions')->sole();
        $this->assertSame((int) $this->hqStaff('content')->id, (int) $decision->decided_by);
        $this->assertNotNull($decision->decided_at);

        $this->post('/saas/reading/shelves', ['name' => 'Stories for Grades 4 to 6', 'grade_from' => 4, 'grade_to' => 6, 'description' => 'Short classics', 'active' => '1'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $shelf = ReadingShelf::query()->sole();
        $this->put('/saas/reading/shelves/'.$shelf->id.'/books', ['book_list' => (string) $book->id, 'revision' => $shelf->revision])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/saas/reading/shelves/'.$shelf->id)->assertOk()->assertSee('The Synthetic Garden');
        $this->post('/saas/reading/books/'.$book->id.'/questions', ['prompt' => 'What did the children count?', 'option_1' => 'Flowers', 'option_2' => 'Stars', 'option_3' => '',
            'right_option' => '1', 'explanation' => 'They counted the flowers one by one.'])->assertRedirect()->assertSessionHasNoErrors();
        $question = ReadingQuestion::query()->sole();
        $this->assertSame(['Flowers', 'Stars'], $question->options);
        $this->assertSame(0, $question->answer);
        $this->assertSame(1, $this->mirrorCalls());

        // HQ actions belong to no school.
        $actions = AuditLog::query()->where('action', 'like', 'reading_%')->pluck('action')->all();
        $this->assertContains('reading_rights_decided', $actions);
        $this->assertSame(0, AuditLog::query()->where('action', 'like', 'reading_%')->whereNotNull('school_id')->count());

        // Asha (Grade 5) sees the shelf and reads the book from the server's copy.
        $shelves = $this->as('asha')->getJson('/api/v2/reading/shelves')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame('Stories for Grades 4 to 6', $shelves['shelves'][0]['name']);
        $this->assertSame('The Synthetic Garden', $shelves['shelves'][0]['books'][0]['title']);
        $this->assertFalse($shelves['ai_checks']);
        $first = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk()->json('data');
        $this->assertGreaterThan(1, $first['page_count']);
        $this->assertStringStartsWith('Paragraph 1.', $first['text']);
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/2')->assertOk();
        foreach (range(1, $first['page_count']) as $page) {
            $text = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/'.$page)->assertOk()->json('data.text');
            $this->assertStringNotContainsStringIgnoringCase('gutenberg', $text, 'The licence and the Project Gutenberg name never reach learners.');
            $this->assertStringNotContainsString('Translator:', $text);
        }
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/'.($first['page_count'] + 1))->assertNotFound();
        $this->assertSame(1, $this->mirrorCalls(), 'The text is fetched once and served from the cache afterwards.');
        $this->assertSame(1, ReadingBookText::query()->sole()->removed_lines);

        // Learner-facing book details carry no rights decision or staff names.
        $detail = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id)->assertOk()->json('data');
        $this->assertSame(['total' => 1, 'open' => 0, 'answered' => 0], $detail['checks']);
        $this->assertArrayNotHasKey('rights_status', $detail);
    }

    public function test_the_wrong_hq_role_and_school_staff_cannot_change_the_catalogue(): void
    {
        ['book' => $book, 'shelf' => $shelf] = $this->catalogue();
        $before = [ReadingBook::query()->count(), ReadingShelf::query()->count(), DB::table('reading_shelf_books')->count()];

        $this->signInAs($this->hqStaff('finance'));
        $this->get('/saas/reading')->assertForbidden();
        $this->post('/saas/reading/books', ['gutenberg_id' => 90002, 'title' => 'Nope', 'author' => 'Nobody', 'language' => 'en', 'reading_level' => 'fluent'])->assertForbidden();
        $this->post('/saas/reading/books/'.$book->id.'/rights', ['decision' => 'not_cleared', 'basis' => 'Trying to change the decision.', 'revision' => $book->revision])->assertForbidden();
        $this->signInAs($this->hqStaff('reviewer'));
        $this->put('/saas/reading/shelves/'.$shelf->id.'/books', ['book_ids' => [], 'revision' => $shelf->revision])->assertForbidden();

        // A school's Super Admin is not platform staff.
        $this->signInAs($this->superAdmin());
        $this->get('/saas/reading')->assertForbidden();
        $this->post('/saas/reading/shelves', ['name' => 'School shelf', 'grade_from' => 1, 'grade_to' => 2])->assertForbidden();

        // Even platform code running inside a school cannot write the catalogue.
        app(CurrentSchool::class)->run(1, function () use ($book) {
            $this->refuses(fn () => ReadingBook::query()->whereKey($book->id)->first()->update(['title' => 'Changed in a school']), 403);
            $this->refuses(fn () => app(ReadingCatalogue::class)->createShelf($this->hqStaff('content'), ['name' => 'From a school', 'grade_from' => 1, 'grade_to' => 3]), 403);
        });
        $this->assertSame($before, [ReadingBook::query()->count(), ReadingShelf::query()->count(), DB::table('reading_shelf_books')->count()]);
        $this->assertSame('The Synthetic Garden', $book->fresh()->title);
    }

    public function test_a_book_without_a_positive_rights_decision_is_never_served(): void
    {
        $this->fakeMirror();
        ['book' => $book] = $this->catalogue([], false);
        $this->assertSame([], $this->as('asha')->getJson('/api/v2/reading/shelves')->assertOk()->json('data.shelves.0.books'));
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id)->assertNotFound();
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertNotFound();
        $this->assertSame(0, $this->mirrorCalls(), 'Nothing is fetched for a book that cannot be served.');

        // "Not cleared" is a decision too, and refuses the same way.
        $this->asHq(fn () => app(ReadingCatalogue::class)->decideRights($this->hqStaff(), $book->id, ['decision' => 'not_cleared',
            'basis' => 'The translation in this edition is still in copyright.', 'revision' => $book->revision]));
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertNotFound();

        // A death year inside the South African term cannot be cleared.
        $recent = $this->asHq(fn () => app(ReadingCatalogue::class)->createBook($this->hqStaff(), ['gutenberg_id' => 90003, 'title' => 'A Recent Story', 'author' => 'Recent Writer',
            'author_death_year' => 1980, 'language' => 'en', 'reading_level' => 'fluent']));
        try {
            $this->asHq(fn () => app(ReadingCatalogue::class)->decideRights($this->hqStaff(), $recent->id, ['decision' => ReadingBook::CLEARED,
                'basis' => 'Public domain in the United States, not checked here.', 'translator_checked' => true, 'revision' => $recent->revision]));
            $this->fail('A book still in copyright in South Africa was cleared.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('author_death_year', $error->errors());
        }
        $this->assertTrue(ReadingCatalogue::termEnded(1975, CarbonImmutable::parse('2026-11-04')));
        $this->assertFalse(ReadingCatalogue::termEnded(1976, CarbonImmutable::parse('2026-11-04')));
        $this->assertSame('undecided', $recent->fresh()->rights_status);
    }

    public function test_the_module_switch_and_stages_decide_who_reads(): void
    {
        $this->fakeMirror();
        ['book' => $book] = $this->catalogue();

        app(CurrentSchool::class)->run(1, fn () => SchoolModule::query()->where('module', 'reading')->update(['enabled' => false]));
        $this->as('asha')->getJson('/api/v2/reading/shelves')->assertNotFound();
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertNotFound();
        $this->web($this->superAdmin())->get('/reading-library')->assertNotFound();
        app(CurrentSchool::class)->run(1, fn () => SchoolModule::query()->where('module', 'reading')->update(['enabled' => true]));

        // A learner in another grade sees no shelf for Grades 4 to 6, and cannot open its books.
        $grade9 = (int) GradeLevel::query()->where('code', '9')->value('id');
        app(CurrentSchool::class)->run(1, fn () => ClassSchool::query()->whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $grade9]));
        $this->assertSame([], $this->as('cara')->getJson('/api/v2/reading/shelves')->assertOk()->json('data.shelves'));
        $this->as('cara')->getJson('/api/v2/reading/books/'.$book->id)->assertNotFound();

        // The module is for junior and high school stages: an early years learner is refused.
        $grade2 = (int) GradeLevel::query()->where('code', '2')->value('id');
        app(CurrentSchool::class)->run(1, fn () => ClassSchool::query()->whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $grade2]));
        $this->as('cara')->getJson('/api/v2/reading/shelves')->assertNotFound();
        $this->assertSame(0, $this->mirrorCalls());

        // Parents and teachers are not learners.
        $this->as('farid')->getJson('/api/v2/reading/shelves')->assertForbidden();
        $this->as('tara')->getJson('/api/v2/reading/shelves')->assertForbidden();
    }

    public function test_a_school_hides_shelves_and_books_for_its_own_learners_only(): void
    {
        $this->fakeMirror();
        ['book' => $book, 'shelf' => $shelf] = $this->catalogue();
        $admin = $this->superAdmin();
        $this->web($admin)->get('/reading-library')->assertOk()->assertSee('The Synthetic Garden')->assertSee('Hide this shelf from your learners');
        $this->post('/reading-library/hidden', ['kind' => 'book', 'item_id' => $book->id, 'hidden' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $this->as('asha')->getJson('/api/v2/reading/shelves')->assertOk()->json('data.shelves.0.books'));
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertNotFound();

        // School B's learners still see it, and School B's page shows nothing of School A's choice.
        $this->assertSame('The Synthetic Garden', $this->as('asha', $this->schoolB, self::SCHOOL_B_ID)->getJson('/api/v2/reading/shelves')->assertOk()->json('data.shelves.0.books.0.title'));
        $baobab = $this->schoolBAdmin();
        $this->web($baobab)->get('/reading-library')->assertOk()->assertSee('The Synthetic Garden');
        $this->assertFalse(app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(ReadingSchool::class)->overview($baobab))['shelves'][0]['books'][0]['hidden']);
        $this->assertSame(1, ReadingHiddenItem::acrossSchools()->count());
        $this->assertSame(1, (int) ReadingHiddenItem::acrossSchools()->sole()->school_id);

        // A teacher is not a school leader; the catalogue itself never changes from a school.
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->web($teacher)->get('/reading-library')->assertForbidden();
        $this->post('/reading-library/hidden', ['kind' => 'shelf', 'item_id' => $shelf->id, 'hidden' => 1])->assertForbidden();
        $this->assertSame('public_domain_za', $book->fresh()->rights_status);

        // Hiding the shelf hides every book on it; showing it again brings them back.
        $this->web($admin)->post('/reading-library/hidden', ['kind' => 'book', 'item_id' => $book->id, 'hidden' => 0])->assertRedirect();
        $this->post('/reading-library/hidden', ['kind' => 'shelf', 'item_id' => $shelf->id, 'hidden' => 1])->assertRedirect();
        $this->assertSame([], $this->as('asha')->getJson('/api/v2/reading/shelves')->assertOk()->json('data.shelves'));
        $this->web($admin)->post('/reading-library/hidden', ['kind' => 'shelf', 'item_id' => 999999, 'hidden' => 1])->assertNotFound();
    }

    public function test_progress_and_answers_belong_to_the_learner_and_their_school(): void
    {
        $this->fakeMirror();
        ['book' => $book] = $this->catalogue();
        $question = $this->asHq(fn () => app(ReadingCatalogue::class)->addQuestion($this->hqStaff(), $book->id, ['prompt' => 'What did the children count?',
            'options' => ['Flowers', 'Stars'], 'answer' => 0, 'explanation' => 'They counted the flowers.', 'after_page' => 2]));
        $pages = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk()->json('data.page_count');

        // No question before the page is reached, and never the answer before answering.
        $this->assertSame([], $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/questions')->assertOk()->json('data.questions'));
        $this->as('asha')->postJson('/api/v2/reading/questions/'.$question->id.'/answer', ['choice' => 0])->assertNotFound();
        $saved = $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 2, 'seconds' => 300])->assertOk()->json('data');
        $this->assertSame(['page' => 2, 'furthest_page' => 2, 'page_count' => $pages, 'minutes_read' => 5, 'finished' => false, 'finished_on' => null], $saved['progress']);
        $this->assertSame(5, $saved['reading']['minutes_this_week']);
        $open = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/questions')->assertOk()->json('data.questions');
        $this->assertSame(['id', 'after_page', 'prompt', 'options', 'result'], array_keys($open[0]));
        $this->assertNull($open[0]['result']);
        $this->assertStringNotContainsString('counted the flowers', json_encode($open));
        $result = $this->as('asha')->postJson('/api/v2/reading/questions/'.$question->id.'/answer', ['choice' => 1])->assertOk()->json('data');
        $this->assertSame(['question_id' => $question->id, 'choice' => 1, 'correct' => false, 'answer' => 0, 'explanation' => 'They counted the flowers.'], $result);
        // The first answer stands.
        $this->assertFalse($this->as('asha')->postJson('/api/v2/reading/questions/'.$question->id.'/answer', ['choice' => 0])->assertOk()->json('data.correct'));
        $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => $pages + 1, 'seconds' => 10])->assertUnprocessable();
        $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 1, 'seconds' => 10, 'learner_id' => $this->schoolA->ids['ben']])->assertUnprocessable();

        // Ben, in the same class, has none of Asha's progress or answers and cannot answer for her.
        $ben = $this->as('ben')->getJson('/api/v2/reading/books/'.$book->id)->assertOk()->json('data');
        $this->assertNull($ben['progress']);
        $this->assertSame(0, $ben['checks']['answered']);
        $this->assertSame([], $this->as('ben')->getJson('/api/v2/reading/books/'.$book->id.'/questions')->json('data.questions'));
        $this->as('ben')->postJson('/api/v2/reading/questions/'.$question->id.'/answer', ['choice' => 0])->assertNotFound();
        $this->assertSame(0, $this->as('ben')->getJson('/api/v2/reading/shelves')->json('data.reading.minutes_this_week'));

        // School B's Asha shares nothing with School A's, and School A's token cannot be used in School B.
        $baobab = $this->as('asha', $this->schoolB, self::SCHOOL_B_ID)->getJson('/api/v2/reading/books/'.$book->id)->assertOk()->json('data');
        $this->assertNull($baobab['progress']);
        $this->as('asha', $this->schoolA, self::SCHOOL_B_ID)->getJson('/api/v2/reading/shelves')->assertForbidden();
        $this->assertSame(1, ReadingProgress::acrossSchools()->count());
        $this->assertSame([1], ReadingAnswer::acrossSchools()->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(1, $this->mirrorCalls());
    }

    public function test_fetch_errors_oversize_and_wrong_types_are_handled(): void
    {
        ['book' => $book] = $this->catalogue();
        $fetchNow = fn () => $this->asHq(fn () => app(ReadingCatalogue::class)->fetchNow($this->hqStaff(), $book->id));
        $reason = function () use ($fetchNow): string {
            try {
                $fetchNow();
            } catch (ValidationException) {
                return (string) ReadingBookText::query()->sole()->last_error;
            }
            $this->fail('Expected the fetch to fail.');
        };

        config(['reading.mirror_url' => null]);
        $this->assertSame('no_mirror', $reason());
        config(['reading.mirror_url' => 'http://mirror.example.org']);
        $this->assertSame('mirror_invalid', $reason(), 'Only https unless explicitly allowed.');
        config(['reading.mirror_url' => self::MIRROR]);

        $this->fakeMirror('Server error', 500);
        $this->assertSame('http_error', $reason());
        $this->fakeMirror('', 302, 'text/plain', ['Location' => 'https://elsewhere.example/']);
        $this->assertSame('http_error', $reason(), 'Redirects are not followed.');
        $this->fakeMirror('<html>Mirror page</html>', 200, 'text/html');
        $this->assertSame('wrong_type', $reason());
        $this->mirrorAnswer = fn () => throw new ConnectionException('timed out');
        $this->assertSame('unreachable', $reason());
        $this->fakeMirror("Just a text without the start and end lines\n");
        $this->assertSame('markers_missing', $reason());
        $this->fakeMirror("\xC3\x28 not UTF-8");
        $this->assertSame('not_utf8', $reason());
        config(['reading.max_bytes' => 1000]);
        $this->fakeMirror();
        $this->assertSame('too_large', $reason());
        $this->assertSame('failed', ReadingBookText::query()->sole()->state);
        $this->assertSame([], Storage::disk(ReadingFetcher::DISK)->allFiles());

        // A learner gets "not ready yet", and the mirror is not asked again until the wait is over.
        $calls = $this->mirrorCalls();
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertStatus(503)->assertHeader('Retry-After', '300');
        $this->assertSame($calls, $this->mirrorCalls());
        config(['reading.max_bytes' => 8 * 1024 * 1024]);
        $this->travel(301)->seconds();
        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk();
        $this->assertSame($calls + 1, $this->mirrorCalls());
        $this->assertNull(ReadingBookText::query()->sole()->last_error);

        // The configured host and path are used, never anything from the request.
        Http::assertSent(fn ($request) => $request->url() === self::MIRROR.'/cache/epub/90001/pg90001.txt');
    }

    public function test_a_reader_waiting_on_the_lock_respects_a_failure_recorded_meanwhile(): void
    {
        ['book' => $book] = $this->catalogue();
        $this->fakeMirror('Server error', 500);
        ReadingBookText::query()->create(['reading_book_id' => $book->id, 'state' => 'missing']);
        // Another reader's fetch fails between this reader's first look (no failure yet) and its turn inside the lock.
        $failedMeanwhile = false;
        ReadingBookText::retrieved(function (ReadingBookText $row) use (&$failedMeanwhile) {
            if (! $failedMeanwhile) {
                $failedMeanwhile = true;
                DB::table('reading_book_texts')->where('id', $row->id)->update(['state' => 'failed', 'last_error' => 'http_error', 'last_failed_at' => now()]);
            }
        });

        $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertStatus(503)->assertHeader('Retry-After', '300');
        $this->assertTrue($failedMeanwhile);
        $this->assertSame(0, $this->mirrorCalls(), 'The waiting reader does not ask the failing mirror again.');

        // HQ can still force a fetch at once.
        $this->fakeMirror();
        $this->asHq(fn () => app(ReadingCatalogue::class)->fetchNow($this->hqStaff(), $book->id));
        $this->assertSame(1, $this->mirrorCalls());
        $this->assertSame('ready', ReadingBookText::query()->sole()->state);
    }

    public function test_a_new_gutenberg_number_drops_the_cached_text_of_the_old_one(): void
    {
        ['book' => $book] = $this->catalogue();
        $this->fakeMirror($this->gutenbergFile(3));
        $this->assertStringStartsWith('Paragraph 1.', $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk()->json('data.text'));
        $oldFiles = Storage::disk(ReadingFetcher::DISK)->allFiles();
        $this->assertCount(3, $oldFiles);

        $this->asHq(function () use ($book) {
            $catalogue = app(ReadingCatalogue::class);
            $changed = $catalogue->updateBook($this->hqStaff(), $book->id, ['gutenberg_id' => 90077, 'title' => 'Another Garden', 'author' => 'Ada Example', 'author_birth_year' => 1850,
                'author_death_year' => 1900, 'language' => 'en', 'subjects' => 'Gardens', 'reading_level' => 'developing', 'revision' => $book->fresh()->revision]);
            $this->assertSame('undecided', $changed->rights_status);
            $catalogue->decideRights($this->hqStaff(), $book->id, ['decision' => ReadingBook::CLEARED, 'basis' => 'Author died in 1900; the new edition has no translator.',
                'translator_checked' => true, 'revision' => $changed->revision]);
        });
        $this->assertSame('missing', ReadingBookText::query()->sole()->state);
        $this->assertSame([], Storage::disk(ReadingFetcher::DISK)->allFiles(), 'The old text, pages and header are deleted.');

        $other = str_replace('Paragraph', 'Section', $this->gutenbergFile(3, 'ANOTHER GARDEN'));
        $this->fakeMirror($other);
        $this->assertStringStartsWith('Section 1.', $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk()->json('data.text'));
        $this->assertSame(2, $this->mirrorCalls());
        Http::assertSent(fn ($request) => $request->url() === self::MIRROR.'/cache/epub/90077/pg90077.txt');
    }

    public function test_reading_badges_are_awarded_once_through_recognition(): void
    {
        $this->fakeMirror();
        ['book' => $book] = $this->catalogue();
        $pages = $this->as('asha')->getJson('/api/v2/reading/books/'.$book->id.'/pages/1')->assertOk()->json('data.page_count');
        $admin = $this->superAdmin();
        // Awards need each learner's current placement.
        app(CurrentSchool::class)->run(1, function () {
            foreach (['asha', 'ben'] as $learner) {
                Enrolment::query()->create(['learner_id' => $this->schoolA->ids[$learner], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'],
                    'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic reading fixture']);
            }
        });

        // Both start off: nothing is given.
        $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 1, 'seconds' => 120])->assertOk();
        $this->assertSame(0, RecognitionBadge::acrossSchools()->count());

        $this->web($admin)->put('/reading-library/settings', ['finished_badge' => 1, 'days_badge' => 1, 'days_required' => 9, 'revision' => 0])->assertSessionHasErrors('days_required');
        $this->put('/reading-library/settings', ['finished_badge' => 1, 'days_badge' => 1, 'days_required' => 2, 'revision' => 0])->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/reading-library/settings', ['finished_badge' => 1, 'days_badge' => 1, 'days_required' => 2, 'revision' => 0])->assertStatus(409);

        $finished = $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => $pages, 'seconds' => 60])->assertOk()->json('data');
        $this->assertTrue($finished['progress']['finished']);
        $this->assertSame(['Finished reading The Synthetic Garden'], $finished['badges']);
        // Reading the last page again gives nothing more.
        $this->assertSame([], $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => $pages, 'seconds' => 60])->assertOk()->json('data.badges'));

        // A second reading day in the same week.
        $this->travel(1)->days();
        $this->assertSame(['Read on 2 days in one week'], $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 3, 'seconds' => 90])->assertOk()->json('data.badges'));
        $this->travel(1)->days();
        $this->assertSame([], $this->as('asha')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 3, 'seconds' => 90])->assertOk()->json('data.badges'));

        $awards = AwardPresentation::acrossSchools()->orderBy('id')->get();
        $this->assertSame(['badge', 'badge'], $awards->pluck('source_kind')->all());
        $this->assertSame([(int) $this->schoolA->ids['asha'], (int) $this->schoolA->ids['asha']], $awards->pluck('learner_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([1, 1], $awards->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(['reading_finished', 'reading_days'], RecognitionBadge::acrossSchools()->orderBy('id')->pluck('kind')->all());

        // Ben's reading is his own: Asha's days and books never count for him.
        $ben = $this->as('ben')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => 1, 'seconds' => 120])->assertOk()->json('data');
        $this->assertSame([], $ben['badges']);
        $this->assertSame(0, $ben['reading']['books_finished']);

        // With the awards module off nothing is given.
        app(CurrentSchool::class)->run(1, fn () => SchoolModule::query()->where('module', 'awards')->update(['enabled' => false]));
        $this->assertSame([], $this->as('ben')->putJson('/api/v2/reading/books/'.$book->id.'/progress', ['page' => $pages, 'seconds' => 60])->assertOk()->json('data.badges'));
        $this->assertSame(2, RecognitionBadge::acrossSchools()->count());
    }

    public function test_school_settings_are_refused_to_other_roles_and_never_cross_schools(): void
    {
        $this->catalogue();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(CurrentSchool::class)->run(1, fn () => app(ReadingSchool::class)->configure($teacher,
            ['finished_badge' => true, 'days_badge' => false, 'days_required' => 3, 'revision' => 0])), 403);
        app(CurrentSchool::class)->run(1, fn () => app(ReadingSchool::class)->configure($this->superAdmin(), ['finished_badge' => true, 'days_badge' => false, 'days_required' => 3, 'revision' => 0]));
        $schoolB = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(ReadingSchool::class)->overview($this->schoolBAdmin()));
        $this->assertFalse($schoolB['settings']['finished_badge']);
        $this->assertSame(0, $schoolB['settings']['revision']);
    }

    public function test_every_reading_table_is_sorted_for_tenancy(): void
    {
        foreach (['reading_books', 'reading_rights_decisions', 'reading_book_texts', 'reading_shelves', 'reading_shelf_books', 'reading_questions'] as $table) {
            $this->assertArrayHasKey($table, SchoolOwnedTables::GLOBAL);
        }
        foreach (['reading_hidden_items', 'reading_settings', 'reading_progress', 'reading_days', 'reading_answers'] as $table) {
            $this->assertTrue(SchoolOwnedTables::isSchoolOwned($table));
        }
    }
}
