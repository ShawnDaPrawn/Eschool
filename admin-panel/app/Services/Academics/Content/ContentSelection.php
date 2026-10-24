<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\ContentItem;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\GradeLevel;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use Illuminate\Support\Facades\Validator;

class ContentSelection
{
    public static function rules(bool $create = true): array
    {
        return ['title' => ['required', 'string', 'min:3', 'max:191'], 'summary' => ['present', 'nullable', 'string', 'max:2000'],
            'group_ids' => ['required', 'array', 'min:1', 'max:'.config('academic_content.maximum_groups')], 'group_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'topic_ids' => ['present', 'array', 'max:'.config('academic_content.maximum_topics')], 'topic_ids.*' => ['required', 'integer', 'min:1', 'distinct']]
            + ($create ? ['subject_id' => ['required', 'integer', 'min:1'], 'grade_level_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1']] : []);
    }

    public function normalise(User $actor, array $input, ?ContentItem $item = null): array
    {
        if (is_string($input['title'] ?? null)) {
            $input['title'] = trim($input['title']);
        }
        $data = Validator::make($input, self::rules($item === null))->validate();
        if ($item !== null) {
            foreach (['subject_id', 'grade_level_id', 'session_year_id'] as $key) {
                abort_if(isset($input[$key]) && (int) $input[$key] !== (int) $item->getAttribute($key), 422, __('A content item keeps its subject, grade and school year. Create another item to change context.'));
            }
        }
        $subject = (int) ($item?->subject_id ?? $data['subject_id']);
        $grade = (int) ($item?->grade_level_id ?? $data['grade_level_id']);
        $year = (int) ($item?->session_year_id ?? $data['session_year_id']);
        Subject::findOrFail($subject);
        GradeLevel::findOrFail($grade);
        SessionYear::findOrFail($year);
        $groups = array_map('intval', $data['group_ids']);
        sort($groups);
        foreach ($groups as $id) {
            $g = app(ContentPolicy::class)->group($actor, $id);
            abort_unless((int) $g->subject_id === $subject && (int) $g->grade_level_id === $grade && (int) $g->session_year_id === $year, 422, __('Choose groups for the same subject, grade and year.'));
        }
        $topics = array_map('intval', $data['topic_ids']);
        sort($topics);
        foreach ($topics as $id) {
            CurriculumTopic::whereHas('curriculum', fn ($q) => $q->where('subject_id', $subject)->where('grade_level_id', $grade)->where('session_year_id', $year)->whereHas('assessments', fn ($a) => $a->where('state', 'published')))->findOrFail($id);
        }

        return ['subject_id' => $subject, 'grade_level_id' => $grade, 'session_year_id' => $year, 'title' => $data['title'], 'summary' => trim((string) $data['summary']), 'group_ids' => $groups, 'topic_ids' => $topics];
    }
}
