<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SchoolImportRunRequest;
use App\Services\People\SchoolImports;
use App\Services\Platform\Saas\PlatformSchoolImports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * HQ: a school's onboarding imports, run by Operations on the school's behalf
 * with its approval (Action Plan SA.11). engineering/specs/platform/hq-imports.md.
 */
class PlatformSchoolImportController extends Controller
{
    public function __construct(private readonly PlatformSchoolImports $imports) {}

    public function index(int $school): View
    {
        return $this->page($school, [], null);
    }

    /** Checks the file in the school as the approving leader; nothing is written. */
    public function preview(SchoolImportRunRequest $request, int $school): View|Response
    {
        $input = $this->input($request);
        try {
            $check = $this->imports->preview($request->user(), $school, $input);
        } catch (ValidationException $e) {
            return response($this->page($school, $input, null, $e->errors()), 422);
        }

        return $this->page($school, $input, $check);
    }

    public function store(SchoolImportRunRequest $request, int $school): RedirectResponse|Response
    {
        $input = $request->validated();
        try {
            $import = $this->imports->request($request->user(), $school, $input);
        } catch (ValidationException $e) {
            try {
                $check = $this->imports->preview($request->user(), $school, ['mapped' => true] + $input);
            } catch (ValidationException) {
                $check = null;
            }

            return response($this->page($school, $input, $check, $e->errors()), 422);
        }

        return redirect()->route('saas.imports.show', $import->id)->with('success', __('Sent to the school for approval. It can be applied once a leader approves it.'));
    }

    public function show(int $import): View
    {
        $details = $this->imports->show($import);

        return view('saas.import', ['import' => $details, 'school' => $this->imports->school($details['school_id'])]);
    }

    public function apply(SchoolImportRunRequest $request, int $import): RedirectResponse
    {
        $counts = $this->imports->apply($request->user(), $import, $request->validated());

        return redirect()->route('saas.imports.show', $import)->with('success', __('Imported: :counts.', [
            'counts' => collect($counts)->map(fn (int $count, string $key) => str_replace('_', ' ', $key).' '.$count)->implode(', '),
        ]));
    }

    public function cancel(SchoolImportRunRequest $request, int $import): RedirectResponse
    {
        $this->imports->cancel($request->user(), $import, $request->validated());

        return redirect()->route('saas.imports.show', $import)->with('success', __('Import cancelled and its file deleted.'));
    }

    /**
     * The form's values, with an uploaded file read into csv. A new file has
     * columns nobody has seen yet, so the suggested mapping is used for it.
     *
     * @return array<string, mixed>
     */
    private function input(SchoolImportRunRequest $request): array
    {
        $input = $request->safe()->except('file');
        $file = $request->file('file');
        if ($file !== null) {
            $input['csv'] = SchoolImports::normalise((string) file_get_contents($file->getRealPath()));
            $input['mapped'] = false;
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>|null  $check
     * @param  array<string, list<string>>  $errors
     */
    private function page(int $school, array $input, ?array $check, array $errors = []): View
    {
        $row = $this->imports->school($school);
        $kind = (string) ($input['kind'] ?? 'learners');

        return view('saas.imports', [
            'school' => $row,
            'imports' => $this->imports->list($school),
            'approvers' => $this->imports->approvers($school),
            'choices' => $this->imports->choices($school),
            'kind' => array_key_exists($kind, PlatformSchoolImports::KINDS) ? $kind : 'learners',
            'fields' => app(SchoolImports::class)->kind(array_key_exists($kind, PlatformSchoolImports::KINDS) ? $kind : 'learners')->fields(),
            'approverId' => (int) ($input['approver_id'] ?? 0),
            'csv' => (string) ($input['csv'] ?? ''),
            'reason' => (string) ($input['reason'] ?? ''),
            'check' => $check,
            'problems' => collect($errors)->flatten()->map(fn ($problem) => (string) $problem)->values()->all(),
        ]);
    }
}
