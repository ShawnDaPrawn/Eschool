<?php

declare(strict_types=1);

namespace Tests\Feature\EarlyYears;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\ClassSchool;
use App\Models\EarlyYears\DevelopmentArea;
use App\Models\EarlyYears\DiaryEntry;
use App\Models\EarlyYears\EarlyConcern;
use App\Models\EarlyYears\Observation;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Growth\Intervention;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\EarlyYears\DailyDiary;
use App\Services\EarlyYears\DevelopmentAreas;
use App\Services\EarlyYears\Observations;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryQueries;
use App\Services\Gallery\GalleryTags;
use App\Services\Growth\Interventions;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Early years tools, first slice (Action Plan 4.3): daily diary, observations
 * and portfolios, and early concerns referred as interventions.
 * engineering/specs/learning/early-years.md.
 */
class EarlyYearsToolsTest extends TestCase
{
    use UsesTwoSchools;

    private User $tara;

    private User $theo;

    private User $parent;

    private int $year;

    private int $classA;

    private int $asha;

    private int $ben;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T10:00:00+02:00'));
        config(['modules.early_years' => ['always_on' => true], 'modules.gallery' => ['always_on' => true], 'modules.early_warning' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $ids = $this->schoolA->ids;
        $this->year = (int) $ids['session_year'];
        $this->classA = (int) $ids['section_a'];
        $this->asha = (int) $ids['asha'];
        $this->ben = (int) $ids['ben'];
        $this->tara = User::findOrFail($ids['tara_user']);
        $this->theo = User::findOrFail($ids['theo_user']);
        $this->parent = User::findOrFail($ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        foreach (['asha' => 'section_a', 'ben' => 'section_a', 'cara' => 'section_c'] as $learner => $section) {
            Enrolment::create(['learner_id' => $ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $ids[$section], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        }
        $this->allocate('tara', 'section_a');
        $this->allocate('theo', 'section_c');
        // Asha's father may approve her photo tags; nobody may approve Ben's.
        GuardianLink::updateOrCreate(['learner_id' => $this->asha, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        Storage::fake(GalleryPhotos::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-clean', 'signature' => null];
            }
        });
    }

    private function allocate(string $teacher, string $section): void
    {
        HomeroomAllocation::create(['teacher_id' => $this->schoolA->ids["{$teacher}_teacher"], 'class_section_id' => $this->schoolA->ids[$section], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic homeroom teacher', 'revision' => 1]);
    }

    private function area(): int
    {
        return (int) app(DevelopmentAreas::class)->all()->firstWhere('name', 'Communication')->id;
    }

    public function test_panel_development_configuration_and_record_editor_are_separate_and_scoped(): void
    {
        $this->actingAs($this->superAdmin())->get('/early-years')->assertOk()->assertDontSee('name="description"', false);
        $this->get('/early-years?view=configuration')->assertOk()->assertSee('Development areas')->assertDontSee('name="description"', false);
        $area = $this->area();
        $this->get('/early-years?view=area&area_id='.$area)->assertOk()->assertSee('name="description"', false)->assertSee('Communication');
        $this->post(route('early-years.areas.store'), ['name' => 'Synthetic independence', 'description' => 'Independent tasks', 'position' => 10, 'active' => 1])
            ->assertRedirect(route('early-years.index', ['view' => 'configuration']));
        $this->assertSame(1, DevelopmentArea::where('name', 'Synthetic independence')->count());
        $foreign = app(CurrentSchool::class)->run(2, fn () => DevelopmentArea::create(['name' => 'Foreign private area', 'active' => true, 'position' => 1, 'revision' => 1]));
        $this->get('/early-years?view=area&area_id='.$foreign->id)->assertNotFound();
        $this->actingAs($this->tara)->get('/early-years?view=areas')->assertOk()->assertSee('Communication')->assertDontSee('name="description"', false);
        $this->get('/early-years?view=configuration')->assertForbidden();
        $this->get('/early-years?view=area&area_id='.$area)->assertForbidden();
        $this->actingAs($this->parent)->get('/early-years?view=configuration')->assertForbidden();
    }

    private function observe(User $actor, int $learner, array $input = [], ?UploadedFile $photo = null): Observation
    {
        return app(Observations::class)->record($actor, $learner, $input + ['operation_id' => (string) Str::uuid(), 'development_area_id' => $this->area(),
            'observed_on' => '2026-02-10', 'note' => 'Retold the story of the three goats using the puppets.'], $photo);
    }

    private function refuse(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected a refusal.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException $error) {
            $this->assertSame($status, $error->status);
        }
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return ['diary' => DB::table('early_years_diary_entries')->count(), 'observations' => DB::table('early_years_observations')->count(),
            'concerns' => DB::table('early_years_concerns')->count(), 'photos' => DB::table('gallery_photos')->count(), 'tags' => DB::table('photo_tags')->count(),
            'interventions' => DB::table('interventions')->count()];
    }

    private function gradeHead(): void
    {
        $grade = (int) GradeLevel::where('code', 'R')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        AcademicLeadership::create(['user_id' => $this->theo->id, 'session_year_id' => $this->year, 'grade_level_id' => $grade, 'kind' => 'grade_head',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
    }

    public function test_a_class_teacher_fills_in_the_diary_for_the_whole_class_at_once(): void
    {
        $this->actingAs($this->tara)->get('/early-years/classes/'.$this->classA.'/diary?date=2026-02-10')->assertOk()->assertSee('Daily diary');
        $this->actingAs($this->tara)->post('/early-years/classes/'.$this->classA.'/diary', [
            'date' => '2026-02-10', 'everyone' => ['meals' => 'all', 'rest' => 'slept', 'mood' => '', 'toileting' => ''],
            'rows' => [
                ['learner_id' => $this->asha, 'meals' => 'some', 'mood' => 'happy', 'note' => 'Loved painting with her hands today.', 'revision' => 0],
                ['learner_id' => $this->ben, 'revision' => 0],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $asha = DiaryEntry::where('learner_id', $this->asha)->firstOrFail();
        $ben = DiaryEntry::where('learner_id', $this->ben)->firstOrFail();
        $this->assertSame(['some', 'slept', 'happy', null], [$asha->meals, $asha->rest, $asha->mood, $asha->toileting], 'her own choice wins; blanks take the class choice');
        $this->assertSame(['all', 'slept', null], [$ben->meals, $ben->rest, $ben->mood]);
        $this->assertSame('Loved painting with her hands today.', $asha->note);
        $this->assertStringNotContainsString('painting', (string) DB::table('early_years_diary_entries')->where('id', $asha->id)->value('note'), 'the note is encrypted');
        $this->assertSame([(int) $this->tara->id, $this->classA, $this->year], [(int) $asha->recorded_by, (int) $asha->class_section_id, (int) $asha->session_year_id]);
        $this->actingAs($this->tara)->get('/early-years/classes/'.$this->classA.'/diary?date=2026-02-10')->assertOk()->assertSee('Loved painting with her hands today.');

        // A stale page cannot overwrite a colleague's later save.
        app(DailyDiary::class)->save($this->tara, $this->classA, ['date' => '2026-02-10', 'rows' => [['learner_id' => $this->asha, 'meals' => 'none', 'revision' => 1]]]);
        $this->refuse(409, fn () => app(DailyDiary::class)->save($this->tara, $this->classA, ['date' => '2026-02-10', 'rows' => [['learner_id' => $this->asha, 'meals' => 'most', 'revision' => 1]]]));
        $this->assertSame('none', DiaryEntry::where('learner_id', $this->asha)->value('meals'));
        // Not for a day that has not happened yet.
        $this->refuse(422, fn () => app(DailyDiary::class)->save($this->tara, $this->classA, ['date' => '2026-02-11', 'rows' => [['learner_id' => $this->ben, 'meals' => 'all', 'revision' => 0]]]));
    }

    public function test_observations_build_a_portfolio_by_development_area_from_the_schools_own_list(): void
    {
        $areas = app(DevelopmentAreas::class)->all();
        $this->assertSame(array_column(DevelopmentAreas::DEFAULTS, 0), $areas->pluck('name')->all(), 'South African early learning and development areas by default');
        $this->actingAs($this->tara)->post('/early-years/learners/'.$this->asha.'/observations', ['operation_id' => (string) Str::uuid(), 'development_area_id' => $this->area(),
            'observed_on' => '2026-02-09', 'note' => 'Counted ten blocks and built a tower.'])->assertRedirect()->assertSessionHasNoErrors();
        $row = Observation::firstOrFail();
        $this->assertSame([$this->asha, $this->classA, null], [(int) $row->learner_id, (int) $row->class_section_id, $row->gallery_photo_id]);
        $this->assertStringNotContainsString('blocks', (string) DB::table('early_years_observations')->value('note'));
        $this->actingAs($this->tara)->get('/early-years/learners/'.$this->asha)->assertOk()->assertSee('Communication')->assertSee('Counted ten blocks and built a tower.');
        $this->actingAs($this->tara)->get('/early-years/classes/'.$this->classA)->assertOk()->assertSee('1 observation');

        // The same operation again is the same observation; changed details are refused.
        $op = (string) Str::uuid();
        $first = $this->observe($this->tara, $this->asha, ['operation_id' => $op]);
        $this->assertSame($first->id, $this->observe($this->tara, $this->asha, ['operation_id' => $op])->id);
        $this->refuse(409, fn () => $this->observe($this->tara, $this->asha, ['operation_id' => $op, 'note' => 'Something else entirely.']));
        $this->assertSame(2, Observation::count());

        // A school manager adds an area and switches one off; it cannot be chosen any more.
        $admin = $this->superAdmin();
        $added = app(DevelopmentAreas::class)->save($admin, null, ['name' => 'Physical development', 'active' => true]);
        $creativity = $areas->firstWhere('name', 'Creativity');
        app(DevelopmentAreas::class)->save($admin, (int) $creativity->id, ['name' => 'Creativity', 'active' => false, 'revision' => 1]);
        $this->assertSame(7, DevelopmentArea::count());
        $this->assertSame(7, (int) $added->position);
        $this->refuse(404, fn () => $this->observe($this->tara, $this->asha, ['development_area_id' => $creativity->id]));
        $this->refuse(403, fn () => app(DevelopmentAreas::class)->save($this->tara, null, ['name' => 'Teacher area', 'active' => true]));
    }

    public function test_a_photograph_waits_for_the_familys_consent_through_the_gallery(): void
    {
        $this->actingAs($this->tara)->post('/early-years/learners/'.$this->asha.'/observations', ['operation_id' => (string) Str::uuid(), 'development_area_id' => $this->area(),
            'observed_on' => '2026-02-10', 'photo' => UploadedFile::fake()->image('asha-at-the-sandpit.jpg', 40, 30)])->assertRedirect()->assertSessionHasNoErrors();
        $row = Observation::firstOrFail();
        $photo = GalleryPhoto::findOrFail($row->gallery_photo_id);
        $tag = PhotoTag::where('gallery_photo_id', $photo->id)->firstOrFail();
        $this->assertSame(['family', $this->asha, (int) $this->tara->id, null], [$photo->scope, (int) $photo->owner_learner_id, (int) $photo->uploaded_by, $photo->caption]);
        $this->assertSame([$this->asha, 'proposed', null], [(int) $tag->learner_id, $tag->status, $tag->approval_kind]);
        $this->assertStringNotContainsString('sandpit', $photo->path);
        $this->assertTrue(Storage::disk(GalleryPhotos::DISK)->exists($photo->path));

        // Staff cannot open it before the family decides.
        $url = '/early-years/observations/'.$row->id.'/photo';
        $this->actingAs($this->tara)->get($url)->assertNotFound();
        $this->actingAs($this->tara)->get('/early-years/learners/'.$this->asha)->assertOk()->assertSee('waiting for the family');
        // The family sees it in their pending photos, and approves it there.
        $pending = app(GalleryQueries::class)->family($this->parent, $this->asha, true);
        $this->assertSame([(int) $tag->id], array_column($pending['items'], 'tag_id'));
        app(GalleryTags::class)->decide($this->parent, (int) $tag->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'approve']);
        $response = $this->actingAs($this->tara)->get($url.'?thumbnail=1')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring((string) $response->getContent())[2]);

        // Removing the tag takes it out of the portfolio again.
        app(GalleryTags::class)->remove($this->parent, (int) $tag->id, ['operation_id' => (string) Str::uuid(), 'revision' => 2]);
        $this->actingAs($this->tara)->get($url)->assertNotFound();
        $this->assertFalse((bool) GalleryPhoto::whereKey($photo->id)->value('active'));
    }

    public function test_a_photo_for_a_learner_nobody_can_consent_for_is_refused_and_nothing_is_kept(): void
    {
        $before = $this->counts();
        $this->actingAs($this->tara)->from('/early-years/learners/'.$this->ben)->post('/early-years/learners/'.$this->ben.'/observations', ['operation_id' => (string) Str::uuid(),
            'development_area_id' => $this->area(), 'observed_on' => '2026-02-10', 'note' => 'Built a sandcastle.', 'photo' => UploadedFile::fake()->image('ben.jpg', 40, 30)])
            ->assertRedirect('/early-years/learners/'.$this->ben)->assertSessionHasErrors();
        $this->assertSame($before, $this->counts());
        $this->assertSame([], Storage::disk(GalleryPhotos::DISK)->allFiles());
        $this->refuse(422, fn () => $this->observe($this->tara, $this->ben, [], UploadedFile::fake()->image('ben.jpg', 40, 30)));
        $this->assertSame($before, $this->counts());

        // A guardian whose link cannot approve is not consent either; a note alone is fine.
        GuardianLink::updateOrCreate(['learner_id' => $this->ben, 'guardian_user_id' => $this->schoolA->ids['gina_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_approve' => false]);
        $this->refuse(422, fn () => $this->observe($this->tara, $this->ben, [], UploadedFile::fake()->image('ben.jpg', 40, 30)));
        $this->observe($this->tara, $this->ben);
        $this->assertSame($before['observations'] + 1, Observation::count());
        $this->assertSame($before['photos'], GalleryPhoto::count());
    }

    public function test_an_early_concern_is_referred_to_the_grade_head_as_an_intervention(): void
    {
        $this->gradeHead();
        $observation = $this->observe($this->tara, $this->asha);
        $this->assertSame([['id' => (int) $this->theo->id, 'name' => $this->theo->full_name]], app(Observations::class)->concernOwners($this->asha));
        $this->actingAs($this->tara)->post('/early-years/observations/'.$observation->id.'/concern', ['concern' => 'Covers her ears and leaves the mat whenever the class sings.',
            'owner_id' => $this->theo->id, 'review_on' => '2026-02-24'])->assertRedirect()->assertSessionHasNoErrors();

        $concern = EarlyConcern::firstOrFail();
        $intervention = Intervention::findOrFail($concern->intervention_id);
        $this->assertSame([(int) $this->theo->id, (int) $this->tara->id, 'leadership', 'active', $this->asha], [(int) $intervention->owner_id, (int) $intervention->created_by, $intervention->owner_authority, $intervention->state, (int) $intervention->learner_id]);
        $this->assertStringNotContainsString('ears', (string) DB::table('interventions')->where('id', $intervention->id)->value('action'), 'the words are encrypted');
        // The grade head opens it in the interventions flow; the referring teacher does not.
        $body = app(Interventions::class)->read($this->theo, (int) $intervention->id);
        $this->assertStringContainsString('not a diagnosis', $body['action']);
        $this->assertStringContainsString('Covers her ears and leaves the mat whenever the class sings.', $body['action']);
        $this->refuse(404, fn () => app(Interventions::class)->read($this->tara, (int) $intervention->id));
        // The grade head reads the portfolio but does not write in it.
        $this->actingAs($this->theo)->get('/early-years/learners/'.$this->asha)->assertOk()->assertSee('Early concern referred');
        $this->refuse(404, fn () => $this->observe($this->theo, $this->asha));

        // Once per observation, never to someone who is not a grade head, and the observation stays.
        $this->refuse(409, fn () => app(Observations::class)->raiseConcern($this->tara, (int) $observation->id, ['concern' => 'Again the same thing.', 'owner_id' => $this->theo->id, 'review_on' => '2026-02-24']));
        $other = $this->observe($this->tara, $this->asha);
        $this->refuse(422, fn () => app(Observations::class)->raiseConcern($this->tara, (int) $other->id, ['concern' => 'Something worth a look.', 'owner_id' => $this->tara->id, 'review_on' => '2026-02-24']));
        $this->refuse(422, fn () => app(Observations::class)->withdraw($this->tara, (int) $observation->id, ['revision' => 1]));
        $this->assertSame(1, Intervention::count());
    }

    public function test_wrong_role_is_refused_and_nothing_is_written(): void
    {
        $this->gradeHead();
        $observation = $this->observe($this->tara, $this->asha);
        $before = $this->counts();
        $diary = ['date' => '2026-02-10', 'rows' => [['learner_id' => $this->asha, 'meals' => 'all', 'revision' => 0]]];
        foreach ([$this->parent, User::findOrFail($this->schoolA->ids['asha_user'])] as $family) {
            $this->refuse(403, fn () => app(DailyDiary::class)->save($family, $this->classA, $diary));
            $this->refuse(403, fn () => $this->observe($family, $this->asha));
            $this->refuse(403, fn () => app(Observations::class)->portfolio($family, $this->asha));
            $this->refuse(403, fn () => app(Observations::class)->raiseConcern($family, (int) $observation->id, ['concern' => 'Family member trying this.', 'owner_id' => $this->theo->id, 'review_on' => '2026-02-24']));
        }
        // Staff who are not teachers do not write the diary, whatever else they may do.
        $admin = $this->superAdmin();
        $this->refuse(403, fn () => app(DailyDiary::class)->save($admin, $this->classA, $diary));
        $this->refuse(403, fn () => $this->observe($admin, $this->asha));
        $this->actingAs($admin)->post('/early-years/classes/'.$this->classA.'/diary', $diary)->assertForbidden();
        // Families never reach the panel.
        $this->actingAs($this->parent)->post('/early-years/learners/'.$this->asha.'/observations', ['operation_id' => (string) Str::uuid(), 'development_area_id' => $this->area(),
            'observed_on' => '2026-02-10', 'note' => 'Parent note'])->assertRedirect(route('login'));
        // The module switched off closes the tools.
        config(['modules.early_years' => ['name' => 'Early years', 'default' => false]]);
        $this->refuse(403, fn () => $this->observe($this->tara, $this->asha));
        $this->assertSame($before, $this->counts());
    }

    public function test_wrong_school_ids_are_not_found_and_school_b_is_unchanged(): void
    {
        $b = $this->schoolB->ids;
        $before = $this->counts();
        $schoolB = fn () => [DB::table('early_years_diary_entries')->where('school_id', self::SCHOOL_B_ID)->count(), DB::table('early_years_observations')->where('school_id', self::SCHOOL_B_ID)->count(),
            DB::table('early_years_development_areas')->where('school_id', self::SCHOOL_B_ID)->count(), DB::table('gallery_photos')->where('school_id', self::SCHOOL_B_ID)->count()];
        $untouched = $schoolB();
        $this->actingAs($this->tara)->get('/early-years/classes/'.$b['section_a'].'/diary')->assertNotFound();
        $this->actingAs($this->tara)->post('/early-years/classes/'.$b['section_a'].'/diary', ['date' => '2026-02-10', 'rows' => [['learner_id' => $b['asha'], 'meals' => 'all', 'revision' => 0]]])->assertNotFound();
        $this->actingAs($this->tara)->get('/early-years/learners/'.$b['asha'])->assertNotFound()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->actingAs($this->tara)->post('/early-years/learners/'.$b['asha'].'/observations', ['operation_id' => (string) Str::uuid(), 'development_area_id' => $this->area(),
            'observed_on' => '2026-02-10', 'note' => 'Cross-school note'])->assertNotFound();
        // A School B learner slipped into School A's class sheet stops the whole save.
        $this->refuse(404, fn () => app(DailyDiary::class)->save($this->tara, $this->classA, ['date' => '2026-02-10', 'rows' => [
            ['learner_id' => $this->asha, 'meals' => 'all', 'revision' => 0], ['learner_id' => $b['asha'], 'meals' => 'all', 'revision' => 0]]]));
        $this->assertSame($before, $this->counts());
        $this->assertSame($untouched, $schoolB());

        // A School B observation's id, used from School A, is not found and stays as it was.
        $theirs = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($b): int {
            $area = (int) app(DevelopmentAreas::class)->all()->first()->id;

            return (int) DB::table('early_years_observations')->insertGetId(['school_id' => self::SCHOOL_B_ID, 'learner_id' => $b['asha'], 'class_section_id' => $b['section_a'],
                'session_year_id' => $b['session_year'], 'development_area_id' => $area, 'observed_on' => '2026-02-10', 'note' => null, 'recorded_by' => $b['tara_user'],
                'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('0', 64), 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        });
        $row = (array) DB::table('early_years_observations')->where('id', $theirs)->first();
        $this->actingAs($this->tara)->post('/early-years/observations/'.$theirs.'/withdraw', ['revision' => 1])->assertNotFound();
        $this->actingAs($this->tara)->post('/early-years/observations/'.$theirs.'/concern', ['concern' => 'Cross-school concern text.', 'owner_id' => $this->theo->id, 'review_on' => '2026-02-24'])->assertNotFound();
        $this->actingAs($this->tara)->get('/early-years/observations/'.$theirs.'/photo')->assertNotFound();
        $this->assertSame($row, (array) DB::table('early_years_observations')->where('id', $theirs)->first());
        $this->assertSame(0, DB::table('early_years_concerns')->count());
    }

    public function test_only_the_class_teachers_write_and_only_the_recorder_withdraws(): void
    {
        $observation = $this->observe($this->tara, $this->asha);
        $before = $this->counts();
        // Theo teaches another class: Asha's portfolio and class are not his.
        $this->refuse(404, fn () => $this->observe($this->theo, $this->asha));
        $this->refuse(404, fn () => app(Observations::class)->portfolio($this->theo, $this->asha));
        $this->refuse(404, fn () => app(DailyDiary::class)->save($this->theo, $this->classA, ['date' => '2026-02-10', 'rows' => [['learner_id' => $this->asha, 'meals' => 'all', 'revision' => 0]]]));
        $this->actingAs($this->theo)->get('/early-years/classes/'.$this->classA)->assertNotFound();
        $this->assertSame($before, $this->counts());

        // As a co-teacher of the class he writes, but cannot withdraw Tara's observation.
        $this->allocate('theo', 'section_a');
        $this->observe($this->theo, $this->asha, ['note' => 'Played a clapping game with a friend.']);
        $this->refuse(403, fn () => app(Observations::class)->withdraw($this->theo, (int) $observation->id, ['revision' => 1]));
        $this->assertNull(Observation::findOrFail($observation->id)->withdrawn_at);
        $this->actingAs($this->tara)->post('/early-years/observations/'.$observation->id.'/withdraw', ['revision' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull(Observation::findOrFail($observation->id)->withdrawn_at);
        $this->actingAs($this->tara)->get('/early-years/learners/'.$this->asha)->assertOk()->assertDontSee('three goats using the puppets.</p>', false);
    }
}
