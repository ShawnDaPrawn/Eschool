<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Services\Activities\CommunityAuthority;
use App\Services\Activities\Fixtures\Competitions;
use App\Services\Activities\Fixtures\Fixtures;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Inter-school fixtures and competitions. Authority is checked fresh by the
 * services (before validation of the record and again before any replay);
 * the route's action names its rules.
 */
class FixtureRequest extends FormRequest
{
    public function action(): string
    {
        $name = (string) $this->route()->getName();

        return substr($name, strrpos($name, '.') + 1);
    }

    /** The role check comes before validation, so a wrong role is refused whatever it sends. */
    public function authorize(): bool
    {
        $access = app(CommunityAuthority::class);
        $action = $this->action();
        if ($action === 'learner') {
            $access->actor($this->user());
        } elseif (in_array($action, ['create', 'schedule', 'decision', 'schools', 'competition-create', 'competition-invite', 'competition-decision'], true)) {
            $access->manage($this->user());
        } else {
            $access->staff($this->user(), in_array($action, ['side', 'result'], true));
        }

        return true;
    }

    public function rules(): array
    {
        $action = $this->action();

        return str_starts_with($action, 'competition-') ? Competitions::rules($action) : Fixtures::rules($action);
    }
}
