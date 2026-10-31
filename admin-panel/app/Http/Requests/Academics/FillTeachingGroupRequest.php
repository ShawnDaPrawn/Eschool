<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

class FillTeachingGroupRequest extends TeachingGroupRequest
{
    public function rules(): array
    {
        return ['starts_on' => ['required', 'date_format:Y-m-d'], 'mark_rule' => ['required', 'in:catch_up,excused,carried']];
    }
}
