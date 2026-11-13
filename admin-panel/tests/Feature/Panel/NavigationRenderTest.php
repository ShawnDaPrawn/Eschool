<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\User;
use App\Services\Panel\Navigation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Tests\TestCase;

class NavigationRenderTest extends TestCase
{
    private function navigation(): Navigation
    {
        config(['navigation.sections' => [
            'home' => ['label' => 'Home', 'groups' => [
                'overview' => ['label' => 'Overview', 'items' => [
                    ['key' => 'home', 'label' => 'Home', 'route' => 'home', 'permissions' => [], 'module' => null],
                ]],
            ]],
        ]]);

        return new class extends Navigation
        {
            public int $checks = 0;

            public bool $allowed = true;

            protected function authorityAvailable(array $item, User $user): bool
            {
                $this->checks++;

                return $this->allowed && ! ($item['future_authority_flag'] ?? false);
            }
        };
    }

    private function document(User $user, string $method = 'GET'): Request
    {
        $request = Request::create('/workspace/people', $method);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_one_document_reuses_rendered_menu_but_new_requests_recheck_authority(): void
    {
        $user = new User(['id' => 71]);
        $this->document($user);
        $navigation = $this->navigation();

        $first = $navigation->sections($user);
        $this->assertSame($first, $navigation->sections($user));
        $this->assertCount(1, $navigation->items($user));
        $this->assertSame(1, $navigation->checks);

        // Revocation takes effect on the next request, even on the same URL
        // and service instance. There is no session or cross-request cache.
        $navigation->allowed = false;
        $this->document($user);
        $this->assertSame([], $navigation->sections($user));
        $this->assertSame(2, $navigation->checks);
    }

    public function test_year_actor_school_locale_route_and_registry_changes_do_not_reuse_menus(): void
    {
        $user = new User(['id' => 71]);
        $request = $this->document($user);
        $navigation = $this->navigation();
        $navigation->sections($user);
        $this->assertSame(1, $navigation->checks);

        $request->session()->put('session_year', 22);
        $navigation->sections($user);
        $this->assertSame(2, $navigation->checks);

        $other = new User(['id' => 72]);
        $request->setUserResolver(fn () => $other);
        $navigation->sections($other);
        $this->assertSame(3, $navigation->checks);

        $context = app(CurrentSchool::class);
        $snapshot = $context->snapshot();
        $context->restore(array_replace($snapshot, ['id' => 872]));
        $navigation->sections($other);
        $this->assertSame(4, $navigation->checks);

        app()->setLocale('af');
        $navigation->sections($other);
        $this->assertSame(5, $navigation->checks);

        $request->query->set('tab', 'leave');
        $navigation->sections($other);
        $this->assertSame(6, $navigation->checks);

        config(['navigation.sections.home.groups.overview.items.0.label' => 'Changed']);
        $this->assertSame('Changed', $navigation->sections($other)[0]['groups'][0]['items'][0]['label']);
        $this->assertSame(7, $navigation->checks);
    }

    public function test_writes_other_person_previews_and_explicit_authority_checks_stay_fresh(): void
    {
        $user = new User(['id' => 71]);
        $this->document($user, 'POST');
        $navigation = $this->navigation();
        $navigation->sections($user);
        $navigation->allowed = false;
        $this->assertSame([], $navigation->sections($user));
        $this->assertSame(2, $navigation->checks);

        $this->document($user);
        $preview = new User(['id' => 72]);
        $navigation->allowed = true;
        $navigation->sections($preview);
        $navigation->allowed = false;
        $this->assertSame([], $navigation->sections($preview));
        $this->assertSame(4, $navigation->checks);

        $navigation->allowed = true;
        $navigation->sections($user);
        $navigation->allowed = false;
        $this->assertFalse($navigation->visible(['route' => 'home'], $user));
        $this->assertSame(6, $navigation->checks);
    }

    public function test_repeated_links_share_checks_but_new_authority_flags_remain_distinct(): void
    {
        $user = new User(['id' => 71]);
        $this->document($user);
        $navigation = $this->navigation();
        $item = config('navigation.sections.home.groups.overview.items.0');
        config(['navigation.sections.home.groups.overview.items' => [
            $item,
            array_replace($item, ['key' => 'same-authority', 'label' => 'Another view', 'params' => ['view' => 'configuration']]),
            array_replace($item, ['key' => 'future-guard', 'future_authority_flag' => true]),
        ]]);

        $items = $navigation->items($user);
        $this->assertSame(['home', 'same-authority'], array_column($items, 'key'));
        $this->assertSame(2, $navigation->checks);
        $this->assertStringContainsString('view=configuration', $items[1]['url']);
    }
}
