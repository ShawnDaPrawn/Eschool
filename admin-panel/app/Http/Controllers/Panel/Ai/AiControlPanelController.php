<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiControlRequest;
use App\Services\Ai\Operations\AiPlatformCosts;
use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiControlPanelController extends Controller
{
    public function index(AiControlRequest $request): Response
    {
        return $this->page($request);
    }

    private function page(AiControlRequest $request, array $messages = [], int $status = 200): Response
    {
        $settings = app(AiSchoolControls::class)->data($request->user());
        $schoolId = app(CurrentSchool::class)->id();
        $accountId = (int) $request->user()->id;
        $operationId = (string) Str::uuid();

        return response()->view('ai.controls', compact('settings', 'schoolId', 'accountId', 'operationId', 'messages'), $status);
    }

    public function save(AiControlRequest $request): Response|RedirectResponse
    {
        try {
            app(AiSchoolControls::class)->save($request->user(), $request->all());

            return redirect()->route('ai-controls.index');
        } catch (ValidationException $error) {
            return $this->page($request, $error->errors(), 422);
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return $this->page($request, ['These settings changed. Check the current limits and try again.'], 409);
        }
    }

    public function costs(Request $request): Response
    {
        app(AiPlatformCosts::class)->actor($request->user());
        $report = app(AiPlatformCosts::class)->report($request->user(), $request->string('from', now()->subDays(30)->toDateString())->toString(), $request->string('to', now()->toDateString())->toString(), $request->integer('page', 1));

        return response()->view('ai.platform-costs', compact('report'));
    }
}
