<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Pathways;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pathways\CareerProfilePanelRequest;
use App\Models\Growth\MasteryTopic;
use App\Models\Pathways\AdmissionRequirement;
use App\Models\Subject;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\TopicGraph;
use App\Services\Pathways\CareerProfiles;
use App\Services\Pathways\PathwayPanelScope;
use App\Services\Pathways\PathwayQueries;
use App\Services\Pathways\PathwayReferences;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CareerProfilePanelController extends Controller
{
    public function form(CareerProfilePanelRequest $request): Response
    {
        return $this->failure($request, [], 200);
    }

    public function failure(CareerProfilePanelRequest $request, array $messages, int $status): Response
    {
        app(PathwayPanelScope::class)->authorize($request);
        $configuration = app(PathwayQueries::class)->configuration($request->user());
        $id = $request->route('profile');
        $profile = $id === null ? null : app(CareerProfiles::class)->read($request->user(), (string) $id, true);
        $subjects = collect($configuration['subjects'])->mapWithKeys(fn ($s) => [$s['id'] => $s['name']])->all();
        $linked = $profile === null ? [] : array_merge(...array_column($profile['details']['subjects'], 'subject_ids'));
        foreach (Subject::withTrashed()->whereIn('id', array_diff($linked, array_keys($subjects)))->get() as $subject) {
            $subjects[$subject->id] = $subject->name.' ('.__('retired').')';
        }
        $topics = [];
        foreach (MasteryTopic::query()->distinct()->orderBy('subject_id')->limit(200)->pluck('subject_id') as $subjectId) {
            if (! isset($subjects[$subjectId])) {
                continue;
            }
            foreach (app(TopicGraph::class)->nodes((int) $subjectId) as $node) {
                $topics[$subjects[$subjectId]][(int) $node->id] = $node->name;
            }
        }
        $programmes = [];
        foreach (AdmissionRequirement::where('kind', 'programme')->where('status', 'published')->orderBy('id')->limit(500)->get() as $row) {
            $programmes[$row->id] = app(PathwayReferences::class)->data($row)['title'];
        }
        asort($programmes);

        return response()->view('pathways.career-form', ['configuration' => $configuration, 'profile' => $profile, 'subjects' => $subjects, 'topics' => $topics,
            'programmes' => $programmes, 'today' => app(AcademicAuthority::class)->today(), 'school' => app(CurrentSchool::class)->id(),
            'account' => (int) $request->user()->id, 'messages' => $messages], $status);
    }

    public function save(CareerProfilePanelRequest $request): Response|RedirectResponse
    {
        try {
            app(CareerProfiles::class)->save($request->user(), $request->route('profile'), $request->profileInput());

            return redirect()->route('pathways-panel.index');
        } catch (ValidationException $error) {
            return $this->failure($request, $error->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($request, [$error->getMessage() ?: __('Refresh the career profile before saving.')], $error->getStatusCode());
        }
    }
}
