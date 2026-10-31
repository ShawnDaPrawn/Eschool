<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\Role;
use App\Models\User;
use App\Services\Panel\Navigation;
use App\Services\Platform\Modules\AccessReview;
use App\Services\Platform\Modules\ModuleAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Who can see what (Action Plan 1.15), read-only.
 * Specification: engineering/specs/platform/access-review.md.
 */
class AccessReviewTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function review(): AccessReview
    {
        return app(AccessReview::class);
    }

    public function test_a_custom_roles_levels_abilities_and_pages_are_shown(): void
    {
        $role = Role::query()->create(['name' => 'Front office', 'custom_role' => 1, 'guard_name' => 'web']);
        $role->givePermissionTo([...app(ModuleAccess::class)->permissionsFor('people', 'contribute'), 'profile-sensitive-view', 'profile-sensitive-export', 'role-list']);

        $result = $this->review()->role($this->superAdmin(), (int) $role->id);

        $this->assertSame('contribute', $result['modules']['people']['level']);
        $this->assertSame('none', $result['modules']['academics']['level']);
        $sensitive = collect($result['modules']['people']['abilities'])->firstWhere('sensitive', true);
        $this->assertNotNull($sensitive, 'the sensitive profile ability is shown as sensitive');
        $this->assertSame(['role-list'], $result['other'], 'part of the roles ability is listed on its own');
        $this->assertContains(__('nav_access_review'), array_column($result['pages'], 'label'));
        $this->assertNotContains(__('nav_roles'), array_column($result['pages'], 'label'), 'role-create is needed for the roles page');
    }

    public function test_a_persons_pages_match_their_own_menu_and_the_teacher_sees_their_roles(): void
    {
        $tara = User::findOrFail($this->schoolA->ids['tara_user']);
        $result = $this->review()->person($this->superAdmin(), (int) $tara->id);

        $this->assertSame(['Teacher'], $result['subject']['roles']);
        $own = array_column(app(Navigation::class)->items($tara), 'label');
        $this->assertSame($own, array_column($result['pages'], 'label'));
        $this->assertSame('none', $result['modules']['fees']['level']);
    }

    public function test_only_reviewers_of_this_school_see_it_and_other_schools_people_and_roles_are_not_found(): void
    {
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            try {
                $this->review()->choices(User::findOrFail($this->schoolA->ids[$person]));
                $this->fail($person.' opened the access review');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
        $foreignRole = app(CurrentSchool::class)->run(2, fn () => (int) Role::query()->where('name', 'Teacher')->sole()->id);
        $this->actingAs($this->superAdmin(), 'web')->get(route('access-review.index', ['role' => $foreignRole]))->assertNotFound();
        $this->get(route('access-review.index', ['person' => $this->schoolB->ids['tara_user']]))->assertNotFound();
        $choices = $this->review()->choices($this->superAdmin());
        $this->assertArrayNotHasKey($this->schoolB->ids['tara_user'], $choices['people']);
        $this->assertArrayNotHasKey($this->schoolA->ids['farid_user'], $choices['people'], 'parents are not staff');
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get(route('access-review.index'))->assertForbidden();
    }

    public function test_the_page_shows_a_role_and_a_person(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->get(route('access-review.index'))->assertOk()->assertSee('Review a role')->assertSee('Review a person');
        $teacher = Role::query()->where('name', 'Teacher')->sole();
        $this->get(route('access-review.index', ['role' => $teacher->id]))->assertOk()->assertSee('Access in each module')->assertSee('Pages in their menu');
        $this->get(route('access-review.index', ['person' => $this->schoolA->ids['tara_user']]))->assertOk()->assertSee('Roles at this school: Teacher');
    }
}
