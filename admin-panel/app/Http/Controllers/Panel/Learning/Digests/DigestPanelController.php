<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning\Digests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Digests\DigestPanelRequest;
use App\Services\Learning\Digests\DigestPanelScope;
use App\Services\Learning\Digests\DigestQueries;
use App\Services\Learning\Digests\DigestTemplates;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DigestPanelController extends Controller
{
    public function index(DigestPanelRequest $r): Response
    {
        return $this->workbench($r);
    }

    private function workbench(DigestPanelRequest $r, array $messages = [], int $status = 200): Response
    {
        $listing = app(DigestQueries::class)->templates($r->user());
        $canWrite = app(ModuleGate::class)->writable('digest') && ! app(SessionYearContext::class)->isReadOnly();
        $schoolId = app(CurrentSchool::class)->id();
        $accountId = (int) $r->user()->id;
        $operationId = Str::isUuid($r->input('operation_id', '')) ? $r->input('operation_id') : (string) Str::uuid();
        $draft = $listing['starting_templates']['en'] + ['language' => 'en'];
        if (! $r->isMethodSafe() && $r->route('operation') === 'createTemplate') {
            foreach (['title' => 120, 'body' => 1200, 'notice_prompt' => 120, 'language' => 10] as $key => $maximum) {
                if (is_string($r->input($key))) {
                    $draft[$key] = mb_substr($r->input($key), 0, $maximum);
                }
            }
            foreach (['practice', 'marks', 'attendance', 'calendar'] as $key) {
                if (is_string($r->input('actions.'.$key))) {
                    $draft['actions'][$key] = mb_substr($r->input('actions.'.$key), 0, 160);
                }
            }
        }

        return response()->view('digests.templates', compact('listing', 'canWrite', 'schoolId', 'accountId', 'operationId', 'draft', 'messages'), $status);
    }

    public function write(DigestPanelRequest $r): RedirectResponse|Response
    {
        try {
            if ($r->route('operation') === 'createTemplate') {
                app(DigestTemplates::class)->create($r->user(), $r->safe()->except(['expected_account_id', 'expected_school_id']));
            } else {
                app(DigestTemplates::class)->review($r->user(), (int) $r->route('template'), $r->safe()->except(['expected_account_id', 'expected_school_id']));
            }

            return redirect()->route('digest-panel.index');
        } catch (ValidationException $error) {
            return $this->failure($r, $error->errors(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($r, ['Review the current language version before trying again.'], $error->getStatusCode());
        }
    }

    public function failure(DigestPanelRequest $r, array $messages, int $status): Response
    {
        app(DigestPanelScope::class)->authorize($r);

        return $this->workbench($r, array_values(Arr::flatten($messages)), $status);
    }
}
