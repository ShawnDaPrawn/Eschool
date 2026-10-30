<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\Role;
use App\Models\User;
use App\Services\Platform\Modules\ModuleAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Modules\RoleEditor;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The role editor: custom roles by module and level (Action Plan 1.13).
 * Specification: engineering/specs/platform/role-editor.md.
 */
class RoleEditorTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function editor(): RoleEditor
    {
        return app(RoleEditor::class);
    }

    private function names(Role $role): array
    {
        return $role->fresh()->permissions()->pluck('name')->sort()->values()->all();
    }

    private function expected(array $levels, array $abilities = []): array
    {
        $names = app(ModuleAccess::class)->permissionsForChoices($levels, $abilities);
        sort($names);

        return array_values($names);
    }

    public function test_a_role_is_saved_as_the_permissions_its_levels_and_abilities_map_to(): void
    {
        $role = $this->editor()->save($this->superAdmin(), null, ['name' => 'Front office', 'levels' => ['people' => 'contribute', 'communication' => 'view']]);

        $this->assertSame(1, (int) $role->custom_role);
        $this->assertSame($this->expected(['people' => 'contribute', 'communication' => 'view']), $this->names($role));
        $described = $this->editor()->describe($role->fresh());
        $this->assertSame(['contribute', 'view', 'none'], [$described['modules']['people']['level'], $described['modules']['communication']['level'], $described['modules']['academics']['level']]);
        $this->assertSame([], $described['extras']);
        $this->assertSame(1, AuditLog::query()->where('action', 'role_access_saved')->count());

        // Lowering a level removes what the higher level granted.
        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Front office', 'levels' => ['people' => 'view']]);
        $this->assertSame($this->expected(['people' => 'view']), $this->names($role));
    }

    public function test_sensitive_abilities_need_an_explicit_confirmation_and_are_audited(): void
    {
        $choices = ['name' => 'Admissions', 'levels' => ['people' => 'view'], 'abilities' => ['people' => ['sensitive_profiles']]];
        try {
            $this->editor()->save($this->superAdmin(), null, $choices);
            $this->fail('a sensitive ability was granted without confirmation');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('confirm_sensitive', $error->errors());
        }
        $this->assertNull(Role::query()->where('name', 'Admissions')->first());

        $role = $this->editor()->save($this->superAdmin(), null, $choices + ['confirm_sensitive' => true]);
        $this->assertContains('profile-sensitive-view', $this->names($role));
        $this->assertSame(1, AuditLog::query()->where('action', 'role_sensitive_ability_granted')->count());

        // Removing it needs no confirmation, and is audited too.
        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Admissions', 'levels' => ['people' => 'view']]);
        $this->assertNotContains('profile-sensitive-view', $this->names($role));
        $this->assertSame(1, AuditLog::query()->where('action', 'role_sensitive_ability_removed')->count());
    }

    public function test_a_module_the_school_switched_off_keeps_what_the_role_held_there(): void
    {
        $gate = app(ModuleGate::class);
        $gate->change('groups', true, $this->superAdmin());
        $role = $this->editor()->save($this->superAdmin(), null, ['name' => 'Sport coordinator', 'levels' => ['groups' => 'manage', 'people' => 'view']]);
        $gate->change('groups', false, $this->superAdmin());

        $this->assertFalse($this->editor()->describe($role->fresh())['modules']['groups']['visible']);
        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Sport coordinator', 'levels' => ['people' => 'view']]);

        $this->assertSame($this->expected(['groups' => 'manage', 'people' => 'view']), $this->names($role));
    }

    public function test_a_partial_grant_in_a_switched_off_module_is_kept_even_when_extras_are_dropped(): void
    {
        app(ModuleGate::class)->change('cards', false, $this->superAdmin());
        $role = Role::query()->create(['name' => 'Card clerk', 'custom_role' => 1, 'guard_name' => 'web']);
        $role->givePermissionTo('cards-manage', 'student-list');
        $described = $this->editor()->describe($role->fresh());
        $this->assertSame([false, 'none'], [$described['modules']['cards']['visible'], $described['modules']['cards']['level']]);
        $this->assertSame(['cards-manage', 'student-list'], $described['extras']);

        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Card clerk', 'levels' => ['communication' => 'view'], 'keep_extras' => false]);

        $expected = collect([...$this->expected(['communication' => 'view']), 'cards-manage'])->sort()->values()->all();
        $this->assertSame($expected, $this->names($role), 'the hidden Cards grant stays; the visible People leftover goes');
    }

    public function test_a_failed_save_shows_unticked_abilities_as_unticked(): void
    {
        $choices = ['name' => 'Registrar', 'levels' => [], 'abilities' => ['people' => ['sensitive_profiles'], 'academics' => ['marks_view_all']], 'confirm_sensitive' => true];
        $role = $this->editor()->save($this->superAdmin(), null, $choices);
        $this->editor()->save($this->superAdmin(), null, ['name' => 'Taken', 'levels' => []]);
        $role->givePermissionTo('student-list');
        $edit = route('role-access.edit', $role->id);
        $checked = fn (string $module, string $value) => '/name="abilities\\['.$module.'\\]\\[\\]" value="'.$value.'" checked/';

        $this->actingAs($this->superAdmin(), 'web');
        $page = $this->get($edit)->getContent();
        $this->assertMatchesRegularExpression($checked('people', 'sensitive_profiles'), $page, 'held abilities start ticked');
        $this->assertMatchesRegularExpression($checked('academics', 'marks_view_all'), $page);
        $this->assertMatchesRegularExpression('/name="keep_extras" value="1" checked/', $page);
        // Nothing ticked in People, "Keep them" unticked, and a name that is taken.
        $this->from($edit)->put(route('role-access.update', $role->id), ['name' => 'Taken', 'levels' => ['people' => 'view'], 'keep_extras' => '0'])
            ->assertRedirect($edit)->assertSessionHasErrors('name');
        $page = $this->get($edit)->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression($checked('people', 'sensitive_profiles'), $page);
        $this->assertDoesNotMatchRegularExpression($checked('academics', 'marks_view_all'), $page);
        $this->assertDoesNotMatchRegularExpression('/name="keep_extras" value="1" checked/', $page);
        $this->assertContains('marks-view-all', $this->names($role), 'nothing was saved');
    }

    public function test_permissions_from_the_old_editor_are_kept_unless_dropped(): void
    {
        $role = Role::query()->create(['name' => 'Legacy clerk', 'custom_role' => 1, 'guard_name' => 'web']);
        $role->givePermissionTo('student-list', 'parents-list');

        $described = $this->editor()->describe($role->fresh());
        $this->assertSame('none', $described['modules']['people']['level'], 'two of six view permissions are not a level');
        $this->assertSame(['parents-list', 'student-list'], $described['extras']);

        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Legacy clerk', 'levels' => ['communication' => 'view'], 'keep_extras' => true]);
        $kept = collect([...$this->expected(['communication' => 'view']), 'parents-list', 'student-list'])->unique()->sort()->values()->all();
        $this->assertSame($kept, $this->names($role));

        $this->editor()->save($this->superAdmin(), $role->fresh(), ['name' => 'Legacy clerk', 'levels' => ['communication' => 'view'], 'keep_extras' => false]);
        $this->assertSame($this->expected(['communication' => 'view']), $this->names($role));
    }

    public function test_built_in_and_other_schools_roles_and_people_without_the_right_are_refused(): void
    {
        foreach (['Teacher', 'Super Admin'] as $name) {
            try {
                $this->editor()->save($this->superAdmin(), Role::query()->where('name', $name)->sole(), ['name' => $name, 'levels' => ['people' => 'manage']]);
                $this->fail($name.' was edited');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
        foreach (['Teacher', 'Parent'] as $name) {
            try {
                $this->editor()->save($this->superAdmin(), null, ['name' => $name, 'levels' => []]);
                $this->fail('a built-in name was reused');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('name', $error->errors());
            }
        }
        try {
            $this->editor()->save($this->superAdmin(), null, ['name' => 'Odd', 'levels' => ['not_a_module' => 'manage']]);
            $this->fail('an unknown module was accepted');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('levels', $error->errors());
        }
        $foreign = app(CurrentSchool::class)->run(2, fn () => (int) Role::query()->create(['name' => 'Baobab office', 'custom_role' => 1, 'guard_name' => 'web'])->id);
        $this->actingAs($this->superAdmin(), 'web')->get(route('role-access.edit', $foreign))->assertNotFound();
        $this->put(route('role-access.update', $foreign), ['name' => 'Taken over', 'levels' => ['people' => 'manage']])->assertNotFound();
        $this->get(route('role-access.edit', Role::query()->where('name', 'Teacher')->sole()->id))->assertForbidden();

        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher, 'web')->get(route('role-access.create'))->assertForbidden();
        $this->post(route('role-access.store'), ['name' => 'Self-made', 'levels' => ['people' => 'manage']])->assertForbidden();
        $this->assertNull(Role::query()->where('name', 'Self-made')->first());
        $this->assertSame('Baobab office', app(CurrentSchool::class)->run(2, fn () => Role::query()->findOrFail($foreign)->name));
    }

    public function test_a_role_can_start_from_a_template_and_is_saved_only_when_submitted(): void
    {
        $roles = Role::query()->count();
        $this->actingAs($this->superAdmin(), 'web')->get(route('role-access.create', ['template' => 'bursar']))->assertOk()
            ->assertSee('Started from Bursar or finance officer')->assertSee('value="Bursar or finance officer"', false);
        $this->assertSame($roles, Role::query()->count(), 'opening a template saves nothing');
        $editor = $this->editor()->fromTemplate('bursar');
        $this->assertSame(['manage', 'manage', 'view'], [$editor['modules']['fees']['level'], $editor['modules']['wallet']['level'], $editor['modules']['people']['level']]);
        $this->assertFalse($editor['modules']['fees']['abilities']['fee_billing']['held'], 'approvals are never in a template');
        $this->get(route('role-access.create', ['template' => 'not-a-template']))->assertNotFound();

        $template = config('role_templates.bursar');
        $role = $this->editor()->save($this->superAdmin(), null, ['name' => 'Bursar', 'levels' => $template['levels'], 'abilities' => $template['abilities']]);
        // Modules the school has switched off are not granted from the template.
        $on = array_filter($template['levels'], fn ($level, $module) => app(ModuleGate::class)->allows($module), ARRAY_FILTER_USE_BOTH);
        $this->assertSame($this->expected($on, $template['abilities']), $this->names($role));
    }

    public function test_the_panel_editor_creates_and_updates_a_role(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->get(route('role-access.create'))->assertOk()->assertSee('Create a role by module')->assertSee('People and guardian approval');
        $this->post(route('role-access.store'), ['name' => 'Reception', 'levels' => ['people' => 'view']])->assertRedirect();
        $role = Role::query()->where('name', 'Reception')->sole();
        $this->get(route('role-access.edit', $role->id))->assertOk()->assertSee('Access for Reception');
        $this->put(route('role-access.update', $role->id), ['name' => 'Reception desk', 'levels' => ['people' => 'contribute'], 'keep_extras' => '1'])->assertRedirect(route('role-access.edit', $role->id));
        $this->assertSame(['Reception desk', $this->expected(['people' => 'contribute'])], [$role->fresh()->name, $this->names($role)]);
        $this->get(route('roles.data'))->assertOk()->assertSee('Access by module');
    }
}
