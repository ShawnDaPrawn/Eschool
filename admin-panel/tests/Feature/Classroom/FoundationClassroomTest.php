<?php

declare(strict_types=1);

namespace Tests\Feature\Classroom;

use App\Models\Cards\CardClassroomSession;
use App\Models\Cards\CardOperation;
use App\Models\Cards\CardScanLog;
use App\Models\Classroom\ClassroomEligibility;
use App\Models\Classroom\ClassroomLease;
use App\Models\Learning\PracticeReview;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Classroom\ClassroomAuthority;
use App\Services\Classroom\ClassroomPractice;
use App\Services\Classroom\ClassroomSessions;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class FoundationClassroomTest extends TestCase
{
    use UsesPracticeScenario;

    private array $device;

    private array $grant;

    private array $card;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        SchoolModule::updateOrCreate(['module' => 'cards'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        Storage::fake(CardPortraits::DISK);
        $this->app->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-classroom-fixture', 'signature' => null];
            }
        });
        $this->approve();
        app(CardPortraits::class)->upload($this->actor('farid'), (int) $this->actor('asha')->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('synthetic-classroom.jpg', 120, 150));
        $this->card = app(Cards::class)->issue($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'person_id' => $this->actor('asha')->id, 'type' => 'learner']);
        $this->device = $this->register();
        app(ClassroomAuthority::class)->configure($this->superAdmin(), $this->group, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'eligible' => true, 'reason' => 'Explicit synthetic Foundation classroom eligibility']);
        $this->grant = app(ClassroomAuthority::class)->grant($this->actor(), ['operation_id' => (string) Str::uuid(), 'device_id' => $this->device['id'], 'teaching_group_id' => $this->group, 'minutes' => 30]);
    }

    private function register(string $kind = 'classroom'): array
    {
        return app(CardReaders::class)->register($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic classroom tablet', 'kind' => $kind,
            'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
    }

    private function selection(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'classroom_authorization' => $this->grant['classroom_authorization'], 'locator' => 'NELOC1.1.'.$this->card['id']];
    }

    private function selected(): array
    {
        return app(ClassroomSessions::class)->select($this->device['credential'], $this->selection());
    }

    private function denied(callable $action, array $codes = [401, 403, 404, 409, 422]): void
    {
        try {
            $action();
            $this->fail('A restricted classroom operation succeeded.');
        } catch (ModelNotFoundException|HttpExceptionInterface $error) {
            $this->assertContains($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 404, $codes);
        }
    }

    public function test_exact_selection_stores_only_hashes_and_cannot_mint_normal_login_or_gate_authority(): void
    {
        $input = $this->selection();
        $tokens = PersonalAccessToken::count();
        $sessions = app(ClassroomSessions::class);
        $first = $sessions->select($this->device['credential'], $input);
        $this->assertSame($first, $sessions->select($this->device['credential'], $input));
        $this->assertTrue($first['restricted']);
        $this->assertSame(1, CardClassroomSession::count());
        $this->assertSame(0, CardScanLog::count());
        $this->assertSame($tokens, PersonalAccessToken::count());
        $row = CardClassroomSession::sole();
        $this->assertNull($row->scan_id);
        $secret = explode('.', $first['classroom_session'])[3];
        $this->assertSame(hash('sha256', $secret), $row->secret_hash);
        foreach (CardOperation::where('kind', 'like', 'classroom.%')->get() as $operation) {
            $receipt = json_encode($operation->receipt, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('NEF1.', $receipt);
            $this->assertStringNotContainsString('NECL1.', $receipt);
        }
    }

    public function test_other_device_gate_device_and_wrong_school_locator_cannot_select_or_read(): void
    {
        $first = $this->selected();
        $other = $this->register();
        $gate = $this->register('gate');
        $sessions = app(ClassroomSessions::class);
        $this->denied(fn () => $sessions->authenticate($other['credential'], $first['classroom_session']));
        $this->denied(fn () => $sessions->select($gate['credential'], $this->selection()));
        $bad = $this->selection();
        $bad['locator'] = 'NELOC1.2.'.$this->card['id'];
        $this->denied(fn () => $sessions->select($this->device['credential'], $bad));
        $this->assertSame(1, CardClassroomSession::count());
        $this->assertSame(0, CardScanLog::count());
    }

    public function test_card_freeze_and_restore_do_not_revive_a_retired_learner_credential(): void
    {
        $first = $this->selected();
        app(Cards::class)->freeze($this->superAdmin(), $this->card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic safety stop']);
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
        app(Cards::class)->restore($this->superAdmin(), $this->card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic recovered card']);
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
        $this->assertNotSame($first['classroom_session'], $this->selected()['classroom_session']);
    }

    public function test_guardian_withdrawal_and_reapproval_do_not_restore_old_classroom_identity(): void
    {
        $first = $this->selected();
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        app(ApprovalService::class)->decide($this->actor('farid'), $learner, 'withdraw', config('identity.consent_version'));
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
        app(ApprovalService::class)->decide($this->actor('farid'), $learner, 'approve', config('identity.consent_version'));
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
    }

    public function test_eligibility_change_expiry_and_explicit_leave_revoke_restricted_access(): void
    {
        $first = $this->selected();
        app(ClassroomSessions::class)->leave($this->device['credential'], $first['classroom_session']);
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
        $second = $this->selected();
        $this->travel(16)->minutes();
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $second['classroom_session']));
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        ClassroomEligibility::where('teaching_group_id', $this->group)->update(['eligible' => false, 'revision' => 2]);
        $this->denied(fn () => app(ClassroomSessions::class)->select($this->device['credential'], $this->selection()));
    }

    public function test_restricted_practice_uses_existing_engine_with_exact_one_effect_and_no_marks(): void
    {
        $first = $this->selected();
        $practice = app(ClassroomPractice::class);
        $set = $practice->run($this->device['credential'], $first['classroom_session'], 'issue', ['operation_id' => (string) Str::uuid()]);
        $this->assertSame($this->schoolA->ids['asha'], $set['learner_id']);
        $this->assertSame($this->group, $set['group_id']);
        $input = ['operation_id' => (string) Str::uuid(), 'answer' => '0.5', 'rating' => 3];
        $answer = $practice->run($this->device['credential'], $first['classroom_session'], 'review', $input, $set['id'], $set['items'][0]['id']);
        $this->assertSame($answer, $practice->run($this->device['credential'], $first['classroom_session'], 'review', $input, $set['id'], $set['items'][0]['id']));
        $this->assertSame(1, PracticeReview::count());
        $this->assertSame(0, DB::table('assessment_marks')->count());
        $this->assertSame(0, CardScanLog::count());
    }

    public function test_http_credentials_are_private_body_only_and_refused_by_normal_student_routes(): void
    {
        $first = $this->selected();
        $header = 'Bearer '.$first['classroom_session'].'~'.$this->device['credential'];
        $this->withHeaders(['Authorization' => $header, 'X-School-Id' => '1'])->getJson('/api/v2/foundation-learner/context')->assertOk()->assertJsonPath('data.restricted', true)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/me/context')->assertUnauthorized();
        $this->postJson('/api/v2/foundation-learner/practice?operation_id='.(string) Str::uuid(), ['operation_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/foundation-learner/context')->assertForbidden();
    }

    public function test_readonly_card_inventory_does_not_grant_new_delegations_or_stop_existing_practice(): void
    {
        $first = $this->selected();
        SchoolModule::where('module', 'cards')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']);
        $set = app(ClassroomPractice::class)->run($this->device['credential'], $first['classroom_session'], 'issue', ['operation_id' => (string) Str::uuid()]);
        $this->assertSame($this->group, $set['group_id']);
        $this->denied(fn () => app(ClassroomAuthority::class)->grant($this->actor(), ['operation_id' => (string) Str::uuid(), 'device_id' => $this->device['id'], 'teaching_group_id' => $this->group, 'minutes' => 30]));
    }

    public function test_only_combined_managers_configure_and_only_current_allocated_teachers_grant(): void
    {
        $this->actor('theo')->forceFill(['password_change_required' => false])->save();
        $this->denied(fn () => app(ClassroomAuthority::class)->configure($this->actor(), $this->group,
            ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'eligible' => false, 'reason' => 'Unauthorized policy edit']));
        foreach ([$this->actor('theo'), $this->superAdmin(), $this->actor('farid')] as $outside) {
            $this->denied(fn () => app(ClassroomAuthority::class)->grant($outside,
                ['operation_id' => (string) Str::uuid(), 'device_id' => $this->device['id'], 'teaching_group_id' => $this->group, 'minutes' => 30]));
        }
        $this->assertSame(1, ClassroomLease::count());
    }

    public function test_switching_learner_retires_old_session_and_its_exact_transport_receipt(): void
    {
        $input = $this->selection();
        $first = app(ClassroomSessions::class)->select($this->device['credential'], $input);
        $second = $this->selected();
        $this->assertNotSame($first['classroom_session'], $second['classroom_session']);
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $first['classroom_session']));
        $this->denied(fn () => app(ClassroomSessions::class)->select($this->device['credential'], $input));
        $this->assertSame(1, CardClassroomSession::whereNull('revoked_at')->count());
    }

    public function test_key_rotation_cannot_return_a_false_success_for_an_unusable_grant_replay(): void
    {
        $input = ['operation_id' => (string) Str::uuid(), 'device_id' => $this->device['id'], 'teaching_group_id' => $this->group, 'minutes' => 30];
        $first = app(ClassroomAuthority::class)->grant($this->actor(), $input);
        $this->assertStringStartsWith('NECL1.', $first['classroom_authorization']);
        $original = config('app.key');
        config(['app.previous_keys' => [$original], 'app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->denied(fn () => app(ClassroomAuthority::class)->grant($this->actor(), $input), [409]);
    }

    public function test_retired_practice_source_is_refused_and_invalid_numbers_are_known_422_outcomes(): void
    {
        $selected = $this->selected();
        $practice = app(ClassroomPractice::class);
        $set = $practice->run($this->device['credential'], $selected['classroom_session'], 'issue', ['operation_id' => (string) Str::uuid()]);
        $this->withHeaders(['Authorization' => 'Bearer '.$selected['classroom_session'].'~'.$this->device['credential'], 'X-School-Id' => '1'])
            ->postJson('/api/v2/foundation-learner/practice/'.$set['id'].'/entries/'.$set['items'][0]['id'].'/review',
                ['operation_id' => (string) Str::uuid(), 'answer' => 'not a decimal', 'rating' => 3])->assertUnprocessable();
        DB::table('practice_items')->where('school_id', 1)->where('teaching_group_id', $this->group)->update(['active' => false]);
        $this->denied(fn () => $practice->run($this->device['credential'], $selected['classroom_session'], 'read', [], $set['id']));
        $this->assertSame(0, PracticeReview::count());
    }

    public function test_populated_migration_rollback_refuses_without_changing_delegations(): void
    {
        $migration = require database_path('migrations/2026_10_26_004000_create_foundation_classroom_authority.php');
        try {
            $migration->down();
            $this->fail('Issued Foundation delegation was discarded.');
        } catch (\RuntimeException $expected) {
            $this->assertStringContainsString('retained', $expected->getMessage());
        }
        $this->assertSame(1, ClassroomLease::count());
        $this->assertSame(1, ClassroomEligibility::count());
    }

    public function test_deleted_current_year_cannot_authorize_retained_classroom_sources(): void
    {
        $selected = $this->selected();
        DB::table('session_years')->where('school_id', 1)->where('id', $this->year)->update(['deleted_at' => now()]);
        $this->denied(fn () => app(ClassroomSessions::class)->authenticate($this->device['credential'], $selected['classroom_session']));
    }
}
