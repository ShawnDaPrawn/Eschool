<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The panel half of the tenancy suite (the Build Bible, section 7, security
 * checks): School A's admin opens every panel page and list (the
 * bootstrap-table endpoints), every page that takes an id (with School B's
 * ids), every export and document, and the global search, and none of them
 * holds a School B row. Every School B name, title and sign-in carries the
 * word "Baobab" (UsesTwoSchools); for a PDF the data handed to its view is
 * checked instead of the compressed file.
 */
class PanelTenancyTest extends TestCase
{
    use UsesTwoSchools;

    /** Routes with side effects that no GET should trigger in a test. */
    private const SKIP = [
        'delete-chat-message/cron-job',
    ];

    private const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/', 'up', '_test', '_tenancy'];

    /** What every rendered view was given, for documents (PDFs) whose text is compressed. */
    private string $viewData = '';

    /** @var list<string> */
    private array $problems = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        Mail::fake();
        $this->admin = $this->superAdmin();
        // With the installer's default password every page would ask for a new one.
        $this->admin->forceFill(['password' => Hash::make('tenancy-panel-test')])->save();
        View::composer('*', function ($view) {
            $this->viewData .= (string) json_encode($view->getData(), JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        });
    }

    public function test_every_panel_page_and_list_holds_only_the_admins_school(): void
    {
        $opened = 0;
        foreach ($this->panelGetRoutes() as $route) {
            if (Str::contains($route->uri(), '{')) {
                continue;
            }
            foreach (['' => [], "School B's ids" => $this->schoolBParams()] as $variant => $params) {
                $this->assertHoldsNoSchoolB($this->open('GET', $route->uri(), $params + ['limit' => 100, 'offset' => 0]), $route->uri()." ($variant)");
                $opened++;
            }
        }

        $this->assertGreaterThan(200, $opened, 'Every panel page and list should be opened');
    }

    public function test_every_page_that_takes_an_id_refuses_another_schools(): void
    {
        $opened = 0;
        foreach ($this->panelGetRoutes() as $route) {
            if (! Str::contains($route->uri(), '{')) {
                continue;
            }
            foreach ($this->schoolBRecordIds() as $kind => $id) {
                $uri = preg_replace('/\{[^}]+\}/', (string) $id, $route->uri());
                $this->assertHoldsNoSchoolB($this->open('GET', $uri, $this->schoolBParams()), $route->uri()." (School B's $kind)");
                $opened++;
            }
        }

        $this->assertGreaterThan(100, $opened);
    }

    public function test_exports_and_documents_hold_no_other_schools_rows(): void
    {
        $b = $this->schoolB->ids;
        $documents = [
            ['POST', 'generate-id-card', ['user_id' => implode(',', [$b['asha'], $b['ben'], $b['asha_user']])]],
            ['POST', 'generate-staff-id-card', ['user_id' => implode(',', [$b['tara_user'], $b['theo_user']])]],
            ['POST', 'bonafide-certificate', ['id' => $b['asha'], 'reason' => 'Bank account', 'valid_upto' => now()->addMonth()->format('d-m-Y')]],
            ['POST', 'leaving-certificate', ['id' => $b['asha'], 'reason' => 'Moving']],
            ['GET', "fees/paid/receipt-pdf/{$b['fees_paid']}", []],
            ['GET', "generate-result/{$b['asha']}", []],
            ['GET', "generate-exam-result/{$b['exam']}", ['student_id' => $b['asha']]],
            ['GET', "student-report/{$b['asha']}", []],
            ['GET', "student-attendance/{$b['asha']}", []],
        ];

        foreach ($documents as [$method, $uri, $params]) {
            $this->assertHoldsNoSchoolB($this->open($method, $uri, $params), "$method $uri");
        }

        // The register export is a teacher's: School B's homeroom gives nothing, their own works.
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $week = now()->subDays(6)->format('m/d/Y').' - '.now()->format('m/d/Y');
        $this->assertHoldsNoSchoolB($this->open('POST', 'student/export', ['class_section_id' => $b['section_a'], 'date' => $week], false, $teacher), 'POST student/export');
        $own = $this->open('POST', 'student/export', ['class_section_id' => $this->schoolA->ids['section_a'], 'date' => $week], false, $teacher);
        $own->assertOk();
        $this->assertStringContainsString('Asha', $this->body($own));
    }

    public function test_search_finds_only_the_admins_school(): void
    {
        foreach (['Baobab', 'Asha', 'Tara', 'Maths', 'GR-BAOBAB', 'Fractions'] as $term) {
            // What was found, not the search term the answer repeats.
            $found = $this->open('GET', 'search', ['q' => $term], true);
            $this->assertHoldsNoSchoolB($found, "search for $term", (string) json_encode($found->json('groups')));
            $page = $this->open('GET', 'search', ['q' => $term]);
            $this->assertHoldsNoSchoolB($page, "search page for $term", str_ireplace($term, '', $this->body($page)));
        }
        $this->assertHoldsNoSchoolB($this->open('GET', 'parent/search', ['q' => 'Farid', 'search' => 'Farid'], true), 'parent search');
        $this->assertHoldsNoSchoolB($this->open('GET', 'search-lesson', ['search' => 'Fractions', 'class_section_id' => $this->schoolB->ids['section_a']], true), 'lesson search');

        // The same search finds School A's own records.
        $this->assertStringContainsString('Asha', $this->body($this->open('GET', 'search', ['q' => 'Asha'], true)));
    }

    /** @param array<string, mixed> $params */
    private function open(string $method, string $uri, array $params, bool $json = false, ?User $as = null): TestResponse
    {
        $this->viewData = '';
        $this->app['auth']->forgetGuards();
        $request = $this->actingAs($as ?? $this->admin);
        if ($json) {
            $request = $request->withHeader('Accept', 'application/json');
        }

        return $method === 'GET' ? $request->get('/'.$uri.'?'.http_build_query($params)) : $request->post('/'.$uri, $params);
    }

    /** Collects what is wrong with a response; the test reports them all at the end. */
    private function assertHoldsNoSchoolB(TestResponse $response, string $what, ?string $body = null): void
    {
        $body ??= $this->body($response);
        if ($response->getStatusCode() >= 500) {
            $exception = $response->exception === null ? '' : get_class($response->exception).': '.$response->exception->getMessage().' ';
            $this->problems[] = "$what failed with a server error: ".Str::limit($exception.strip_tags($body), 300);
        }
        if (stripos($body, self::SCHOOL_B_MARKER) !== false) {
            $this->problems[] = "$what showed School A's admin School B's records";
        }
        if (stripos($this->viewData, self::SCHOOL_B_MARKER) !== false) {
            $this->problems[] = "$what handed School B's records to its view";
        }
        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        $problems = $this->problems;
        $this->problems = [];
        parent::tearDown();
        $this->assertSame([], $problems, 'Cross-school problems in the panel');
    }

    private function body(TestResponse $response): string
    {
        $base = $response->baseResponse;
        if ($base instanceof BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }
        if ($base instanceof StreamedResponse) {
            return $response->streamedContent();
        }

        return (string) $response->getContent();
    }

    /** @return list<Route> */
    private function panelGetRoutes(): array
    {
        $routes = [];
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || in_array($uri, self::SKIP, true) || Str::startsWith($uri, self::SKIP_PREFIXES)) {
                continue;
            }
            if (in_array('school.context', $route->gatherMiddleware(), true)) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    /**
     * One School B record of each kind, for {id} in a page's address.
     *
     * @return array<string, int>
     */
    private function schoolBRecordIds(): array
    {
        $b = $this->schoolB->ids;

        return [
            'learner' => $b['asha'], 'learner user' => $b['asha_user'], 'teacher user' => $b['tara_user'], 'parent' => $b['farid'],
            'homeroom' => $b['section_a'], 'class' => $b['class_a'], 'subject' => $b['maths'], 'lesson' => $b['lesson'],
            'exam' => $b['exam'], 'fee payment' => $b['fees_paid'], 'year' => $b['session_year'],
        ];
    }

    /** @return array<string, int> */
    private function schoolBParams(): array
    {
        $b = $this->schoolB->ids;

        return [
            'id' => $b['asha'], 'student_id' => $b['asha'], 'user_id' => $b['asha_user'], 'class_section_id' => $b['section_a'],
            'class_id' => $b['class_a'], 'subject_id' => $b['maths'], 'session_year_id' => $b['session_year'],
            'exam_id' => $b['exam'], 'lesson_id' => $b['lesson'], 'medium_id' => $b['class_a'], 'teacher_id' => $b['tara_teacher'],
        ];
    }
}
