<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class SettingsSectionsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function saveSection(array $input)
    {
        return $this->postJson('/settings', $input + ['expected_account_id' => auth()->id(), 'expected_school_id' => 1]);
    }

    public function test_a_section_save_changes_only_allowlisted_current_school_fields(): void
    {
        $before = Settings::where('type', 'school_name')->value('message');
        $year = schoolSetting('session_year');
        $foreign = app(CurrentSchool::class)->run(2, fn () => Settings::where('type', 'facebook')->value('message'));
        $this->actingAs($this->superAdmin());
        $this->saveSection(['settings_section' => 'links', 'facebook' => 'https://example.org/school', 'instagram' => '', 'linkedin' => '', 'maplink' => '', 'school_name' => 'Must not overwrite', 'session_year' => $this->schoolB->ids['session_year']])->assertOk()->assertJsonPath('error', false);
        $this->assertSame('https://example.org/school', Settings::where('type', 'facebook')->value('message'));
        $this->assertSame($before, Settings::where('type', 'school_name')->value('message'));
        $this->assertSame((string) $year, (string) schoolSetting('session_year'));
        $this->assertSame($foreign, app(CurrentSchool::class)->run(2, fn () => Settings::where('type', 'facebook')->value('message')));
        $this->saveSection(['settings_section' => 'links', 'facebook' => 'https://example.org/stale-school', 'expected_school_id' => 2])->assertForbidden();
        $this->assertSame('https://example.org/school', Settings::where('type', 'facebook')->value('message'));
    }

    public function test_validation_keeps_saved_values_and_wrong_role_cannot_change_settings(): void
    {
        $this->actingAs($this->superAdmin());
        $this->saveSection(['settings_section' => 'school', 'school_name' => 'Should not save', 'school_email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['school_email', 'school_phone']);
        $this->assertNotSame('Should not save', Settings::where('type', 'school_name')->value('message'));
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));
        $this->saveSection(['settings_section' => 'links', 'facebook' => 'https://example.org/forbidden'])->assertRedirect();
        $this->assertNotSame('https://example.org/forbidden', Settings::where('type', 'facebook')->value('message'));
    }

    public function test_section_links_render_separate_forms_and_secret_is_never_reflected(): void
    {
        Settings::updateOrCreate(['type' => 'recaptcha_secret_key'], ['message' => 'synthetic-secret-no-reflection']);
        $this->actingAs($this->superAdmin())->get('/settings?view=school')->assertOk()->assertSee('name="school_name"', false)->assertDontSee('name="recaptcha_secret_key"', false);
        $this->get('/settings?view=security')->assertOk()->assertDontSee('synthetic-secret-no-reflection')->assertDontSee('name="school_name"', false);
    }
}
