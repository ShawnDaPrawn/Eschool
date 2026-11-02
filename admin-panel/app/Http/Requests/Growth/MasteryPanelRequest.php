<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Models\Subject;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Academics\AcademicAccess;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryModels;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Foundation\Http\FormRequest;

class MasteryPanelRequest extends FormRequest
{
    private function action(): string
    {
        return (string) $this->route()?->getActionMethod();
    }

    protected function prepareForValidation(): void
    {
        if ($this->action() === 'save' && ! $this->has('links')) {
            $this->merge(['links' => []]);
        }
    }

    public function authorize(): bool
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery'), 403);
        app(AcademicAuthority::class)->staff($this->user());
        if ($this->action() === 'model') {
            abort_unless($gate->writable('mastery'), 403);
            app(AcademicAccess::class)->actor($this->user(), 'curriculum-publish');
        } elseif (in_array($this->action(), ['save', 'refresh'], true)) {
            abort_unless($gate->writable('mastery'), 403);
            app(AcademicAccess::class)->actor($this->user(), 'curriculum-publish');
            Subject::query()->findOrFail((int) $this->route('subject'));
        } elseif ($this->action() === 'rebuild') {
            abort_unless($gate->writable('mastery'), 403);
            app(MasteryPolicy::class)->staff($this->user(), (int) $this->route('group'));
        }

        return true;
    }

    public function rules(): array
    {
        return match ($this->action()) {
            'save' => TopicGraph::rules() + ['graph_present' => ['required', 'accepted']],
            'model' => MasteryModels::rules(),
            'index' => ['group_id' => ['nullable', 'integer', 'min:1'], 'subject_id' => ['nullable', 'integer', 'min:1'],
                'learner_id' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1'], 'session_year_id' => ['nullable', 'integer', 'min:1']],
            default => [],
        };
    }
}
