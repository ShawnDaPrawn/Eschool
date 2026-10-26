<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Pathways;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pathways\PathwayPanelRequest;
use App\Models\Subject;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pathways\PathwayPanelScope;
use App\Services\Pathways\PathwayQueries;
use App\Services\Pathways\PathwayReferences;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PathwayPanelController extends Controller
{
    public function index(PathwayPanelRequest $request): Response
    {
        $configuration = app(PathwayQueries::class)->configuration($request->user());
        $listing = app(PathwayQueries::class)->catalogue($request->user(), $request->integer('page', 1), true);

        return response()->view('pathways.index', compact('configuration', 'listing'));
    }

    public function form(PathwayPanelRequest $request): Response
    {
        return $this->failure($request, [], 200);
    }

    public function failure(PathwayPanelRequest $request, array $messages, int $status): Response
    {
        app(PathwayPanelScope::class)->authorize($request);
        $configuration = app(PathwayQueries::class)->configuration($request->user());
        $id = $request->route('reference');
        $source = $id === null ? null : app(PathwayQueries::class)->read($request->user(), (string) $id, true);
        $kind = $source['kind'] ?? (string) $request->route('kind', $request->input('kind', 'programme'));
        abort_unless(in_array($kind, ['programme', 'subject_choice_guide'], true), 422);
        $today = app(AcademicAuthority::class)->today();
        $school = app(CurrentSchool::class)->id();
        $account = (int) $request->user()->id;
        $subjects = collect($configuration['subjects'])->mapWithKeys(fn ($s) => [$s['id'] => $s['name']])->all();
        if ($source !== null) {
            $rules = $source['rules'];
            $ids = $kind === 'programme' ? array_column($rules['subjects'], 'subject_id') : array_merge($rules['compulsory_subject_ids'], ...array_column($rules['elective_groups'], 'subject_ids'));
            foreach (Subject::withTrashed()->whereIn('id', array_diff($ids, array_keys($subjects)))->get() as $subject) {
                $subjects[$subject->id] = $subject->name.' ('.__('retired').')';
            }
        }

        return response()->view('pathways.form', compact('configuration', 'source', 'kind', 'today', 'school', 'account', 'subjects', 'messages'), $status);
    }

    public function save(PathwayPanelRequest $request): Response|RedirectResponse
    {
        try {
            app(PathwayReferences::class)->save($request->user(), $request->route('reference'), $request->sourceInput());

            return redirect()->route('pathways-panel.index');
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage() ?: __('Refresh the pathway source before saving.')], $error->getStatusCode());
        }
    }
}
