<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\SessionYear;
use App\Services\Platform\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The school the user is working in, with its branding, timezone and current
 * year (openapi.yaml, CurrentSchool). Colours are passed on as the school
 * stored them; the apps show them only on the crest and "My school", never in
 * place of the NavigateEd theme.
 */
class CurrentSchoolResource extends JsonResource
{
    public function __construct(SchoolProfile $school, private readonly ?SessionYear $sessionYear)
    {
        parent::__construct($school);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SchoolProfile $school */
        $school = $this->resource;

        return [
            ...(new SchoolSummaryResource($school))->toArray($request),
            'tagline' => $school->tagline,
            'branding' => [
                'theme_color' => $school->themeColor,
                'secondary_color' => $school->secondaryColor,
            ],
            'timezone' => $school->timezone,
            'session_year' => $this->sessionYear === null ? null : new SessionYearResource($this->sessionYear),
        ];
    }
}
