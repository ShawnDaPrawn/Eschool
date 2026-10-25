<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ProgressFacilitatorFeaturesTest extends TestCase
{
    use UsesTwoSchools;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->actor = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actor->forceFill(['password_change_required' => false])->save();
        ParentPolicyService::acceptCurrent($this->actor);
    }

    private function features(): array
    {
        return app(FacilitatorAccess::class)->profile($this->actor)['features'];
    }

    public function test_support_and_notes_flags_are_role_specific_and_module_aware(): void
    {
        $this->actor->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertFalse($this->features()['support_plans']);
        $this->assertTrue($this->features()['learner_notes']);
        SchoolModule::updateOrCreate(['module' => 'support_plans'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->assertTrue($this->features()['support_plans']);
        $this->actor->syncRoles([SchoolRoles::FACILITATOR]);
        $this->assertFalse($this->features()['support_plans']);
        $this->assertFalse($this->features()['learner_notes']);
        $this->actor->syncRoles([SchoolRoles::TEACHER]);
        $this->assertTrue($this->features()['support_plans']);
        $this->assertFalse($this->features()['learner_notes']);
    }

    public function test_platform_identity_removes_both_restricted_features_without_loading_record_bodies(): void
    {
        $this->actor->syncRoles([SchoolRoles::COUNSELLOR]);
        SchoolModule::updateOrCreate(['module' => 'support_plans'], ['enabled' => true, 'in_package' => true]);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $this->assertTrue($this->features()['learner_notes']);
        $this->assertTrue($this->features()['support_plans']);
        PlatformStaffIdentity::create(['user_id' => $this->actor->id]);
        $features = $this->features();
        $this->assertFalse($features['support_plans']);
        $this->assertFalse($features['learner_notes']);
        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('from `learner_notes`', $sql);
            $this->assertStringNotContainsString('from `support_plans`', $sql);
            $this->assertStringNotContainsString('from `support_plan_versions`', $sql);
        }
    }

    public function test_discipline_entry_needs_fresh_classroom_or_leadership_rights_and_module(): void
    {
        $this->actor->syncRoles([SchoolRoles::TEACHER]);
        SchoolModule::updateOrCreate(['module' => 'discipline'], ['enabled' => false, 'in_package' => true]);
        $this->assertFalse($this->features()['discipline']);
        SchoolModule::where('module', 'discipline')->update(['enabled' => true, 'read_only' => false]);
        $this->assertTrue($this->features()['discipline']);
        SchoolModule::where('module', 'discipline')->update(['read_only' => true]);
        $this->assertTrue($this->features()['discipline'], 'Read-only modules still expose authorized saved readers.');
        $this->actor->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertFalse($this->features()['discipline'], 'A retained Teacher model cannot keep the classroom queue entry.');
        $this->actor->syncRoles([SchoolRoles::TEACHER]);
        PlatformStaffIdentity::create(['user_id' => $this->actor->id]);
        $this->assertFalse($this->features()['discipline']);
    }
}
