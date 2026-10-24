<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Models\Academics\CurriculumPlan;
use App\Services\Academics\CurriculumPlans;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property CurriculumPlan $resource */
class CurriculumPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = app(CurriculumPlans::class)->data($this->resource);
        if ($data['annual_components'] !== null) {
            $data['annual_components'] = (object) $data['annual_components'];
        }
        foreach ($data['assessments'] as &$assessment) {
            $assessment['components'] = (object) $assessment['components'];
        }
        unset($assessment);

        return $data;
    }
}
