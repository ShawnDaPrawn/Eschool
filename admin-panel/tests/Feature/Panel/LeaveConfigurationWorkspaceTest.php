<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\SessionYear;
use App\Services\Panel\SessionYearContext;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LeaveConfigurationWorkspaceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->superAdmin()->givePermissionTo('leave-setting-create');
    }

    public function test_selected_configuration_year_is_scoped_and_does_not_change_global_year_context(): void
    {
        $year = SessionYear::create(['name' => 'Prepared leave year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'fee_due_date' => '2027-02-01']);
        $current = (int) schoolSetting('session_year');
        $this->actingAs($this->superAdmin())->get(route('leave-master.index', ['configuration_year' => $year->id]))
            ->assertOk()->assertViewHas('configurationYear', fn ($selected) => $selected->id === $year->id)
            ->assertSee('data-configuration-year="'.$year->id.'"', false)->assertSee('Configuration year');
        $this->get(route('leave-master.index', ['view' => 'create', 'configuration_year' => $year->id]))
            ->assertOk()->assertSee('value="'.$year->id.'" selected', false);
        $this->assertSame($current, (int) schoolSetting('session_year'));
        $this->assertSame($current, app(SessionYearContext::class)->selectedId());
        $this->assertNull(session('session_year'));
        $this->get(route('leave-master.index', ['configuration_year' => $this->schoolB->ids['session_year']]))->assertNotFound();
    }
}
