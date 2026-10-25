<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Models\Consultations\Consultation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class ConsultationPanelScope
{
    public function authorize(Request $request): bool
    {
        $access = app(ConsultationAccess::class);
        $operation = (string) $request->route('operation');
        $write = ! $request->isMethodSafe();
        $managed = in_array($operation, ['new', 'edit', 'history', 'create', 'save', 'publish', 'close', 'cancel'], true)
            || ($operation === 'results' && $request->boolean('managed'));
        $actor = $managed ? $access->manager($request->user(), $write) : $access->actor($request->user(), $write);
        if ($write) {
            abort_unless((int) $request->input('expected_account_id') === (int) $actor->id && (int) $request->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($request->query->count() > 0, 422, 'Send consultation changes in the private form.');
        }
        if ($request->route('consultation') !== null) {
            $row = Consultation::findOrFail((int) $request->route('consultation'), ['id', 'school_id', 'session_year_id', 'status', 'audience']);
            if (! $managed) {
                $access->voter($actor, $row, $write);
            } elseif ($write) {
                $access->year((int) $row->session_year_id, true);
            }
        }

        return true;
    }
}
