<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Academics\AcademicLeadership;
use App\Models\Assistance\KnowledgeDocument;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AssistanceQueries
{
    public function available(User $actor): bool
    {
        if (! app(ModuleGate::class)->allows('ask_school')) {
            return false;
        }
        try {
            app(AssistanceAccess::class)->office($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function configuration(User $actor): array
    {
        app(AssistanceAccess::class)->actor($actor);
        $office = $this->available($actor);
        $parent = false;
        try {
            app(AssistanceAccess::class)->parent($actor);
            $parent = true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            abort_unless($office, 403);
        }

        return ['can_parent' => $parent, 'can_office' => $office, 'languages' => config('assistance.languages'),
            'answer_available' => $this->inferenceAvailable('school_assistant'),
            'translation_available' => $this->inferenceAvailable('message_translation')];
    }

    private function inferenceAvailable(string $feature): bool
    {
        $config = config('ai.inference.features.'.$feature);
        if (! config('ai.inference.enabled') || ! is_array($config) || ($config['enabled'] ?? false) !== true
            || ! is_string($config['model'] ?? null) || $config['model'] === ''
            || ! config('ai.providers.'.($config['provider'] ?? '').'.qualified')
            || ! is_int($config['cents_per_million_tokens'] ?? null) || $config['cents_per_million_tokens'] < 1) {
            return false;
        }
        try {
            $config = app(AiSchoolControls::class)->apply($config);
            foreach (AiSchoolControls::KEYS as $key) {
                if (! is_int($config[$key] ?? null) || $config[$key] < 1) {
                    return false;
                }
            }

            return true;
        } catch (AiUnavailable) {
            return false;
        }
    }

    public function officeDocuments(User $actor, int $page = 1): array
    {
        app(AssistanceAccess::class)->office($actor);
        $rows = KnowledgeDocument::orderByDesc('created_at')->orderBy('id')->paginate(20, ['*'], 'page', max(1, $page));
        app(AssistanceAccess::class)->office($actor);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->map(fn ($row) => app(KnowledgeDocuments::class)->metadata($row))->all()];
    }

    public function document(User $actor, string $id, ?int $learner = null): array
    {
        if ($learner === null) {
            app(AssistanceAccess::class)->office($actor);
            $row = KnowledgeDocument::findOrFail($id);
            app(AssistanceAccess::class)->office($actor);
        } else {
            $row = app(KnowledgeDocuments::class)->familyRead($actor, $learner, $id);
            app(AssistanceAccess::class)->family($actor, $learner);
        }

        return app(KnowledgeDocuments::class)->metadata($row) + ['markdown' => $row->markdown];
    }

    public function officeStaff(User $actor): array
    {
        app(AssistanceAccess::class)->office($actor);
        $staff = [];
        $today = app(AcademicAuthority::class)->today();
        $ids = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())
            ->whereIn('role', ['Admin', 'Super Admin'])->pluck('user_id');
        $principals = AcademicLeadership::where('kind', 'principal')->where('session_year_id', (int) schoolSetting('session_year'))
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('user_id');
        foreach (User::whereIn('id', $ids->merge($principals)->unique())->where('status', 1)->orderBy('id')->limit(100)->cursor() as $candidate) {
            if ($this->available($candidate)) {
                $staff[] = ['id' => (int) $candidate->id, 'name' => trim($candidate->first_name.' '.$candidate->last_name)];
            }
            if (count($staff) === 100) {
                break;
            }
        }
        app(AssistanceAccess::class)->office($actor);

        return ['staff' => $staff];
    }
}
