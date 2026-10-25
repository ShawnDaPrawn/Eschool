<?php

declare(strict_types=1);

namespace App\Services\Consultations;

class ConsultationRules
{
    public static function configuration(): array
    {
        return [
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:poll,survey,election'],
            'title' => ['required', 'string', 'max:191'], 'instructions' => ['nullable', 'string', 'max:5000'],
            'opens_at' => ['required', 'date', 'regex:/(Z|[+-][0-9]{2}:[0-9]{2})$/'],
            'closes_at' => ['required', 'date', 'after:opens_at', 'regex:/(Z|[+-][0-9]{2}:[0-9]{2})$/'],
            'anonymous' => ['required', 'boolean'], 'results_visibility' => ['required', 'in:after_close,public_live'],
            'audience' => ['required', 'array:kind,target_id,parents,include_staff'],
            'audience.kind' => ['required', 'in:school,grade,homeroom,group,activity_group,custom'],
            'audience.target_id' => ['nullable', 'integer', 'min:1'], 'audience.parents' => ['sometimes', 'boolean'],
            'audience.include_staff' => ['sometimes', 'boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*' => ['required', 'array:kind,label,required,max_choices,options'],
            'questions.*.kind' => ['required', 'in:single,multiple,text'],
            'questions.*.label' => ['required', 'string', 'max:1000'], 'questions.*.required' => ['required', 'boolean'],
            'questions.*.max_choices' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'questions.*.options' => ['sometimes', 'array', 'max:50'],
            'questions.*.options.*' => ['required', 'array:label,statement'],
            'questions.*.options.*.label' => ['required', 'string', 'max:191'],
            'questions.*.options.*.statement' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
