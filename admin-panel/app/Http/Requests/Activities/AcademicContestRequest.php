<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Services\Activities\CommunityAuthority;
use App\Services\Activities\Fixtures\AcademicContests;
use App\Services\Activities\Fixtures\ReasoningReviews;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Academic competitions and debating (Action Plan 5.3). Authority over the
 * competition itself is checked fresh by the services; the role check here
 * comes before validation, so a wrong role is refused whatever it sends.
 */
class AcademicContestRequest extends FormRequest
{
    public function action(): string
    {
        $name = (string) $this->route()->getName();

        return substr($name, strrpos($name, '.') + 1);
    }

    public function authorize(): bool
    {
        $access = app(CommunityAuthority::class);
        $action = $this->action();
        if ($action === 'learner') {
            $access->actor($this->user());
        } elseif (in_array($action, ['create', 'details', 'adjudicators', 'awards'], true)) {
            $access->manage($this->user());
        } else {
            $access->staff($this->user(), in_array($action, ['result', 'review', 'share'], true));
        }

        return true;
    }

    public function rules(): array
    {
        $action = $this->action();

        return in_array($action, ['review', 'share'], true) ? ReasoningReviews::rules($action) : AcademicContests::rules($action);
    }
}
