<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Http\Requests\Discipline\DisciplinePanelRequest;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class DisciplinePanelScope
{
    public function authorize(Request $request): bool
    {
        abort_if(! $request->isMethodSafe() && $request->query->count() > 0, 422, 'Private changes must be sent in the request body.');
        if (! $request->isMethodSafe()) {
            $account = $request->input('expected_account_id');
            $school = $request->input('expected_school_id');
            abort_unless(is_scalar($account) && is_scalar($school) && (int) $account === (int) $request->user()->id && (int) $school === app(CurrentSchool::class)->id(), 403);
        }
        $scoped = $request instanceof DisciplinePanelRequest ? $request : DisciplinePanelRequest::createFrom($request);
        $scoped->checkAuthority();

        return true;
    }
}
