<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\CashHandoverPanelRequest;
use App\Models\Money\CashHandover;
use App\Models\Money\CashHandoverOutcome;
use App\Models\User;
use App\Policies\Money\CashupPolicy;
use App\Services\Money\CashCustody;
use App\Services\Money\Cashups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CashHandoverPanelController extends Controller
{
    public function index(CashHandoverPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $actor = app(CashupPolicy::class)->finance($request->user());
            $currencies = app(Cashups::class)->currencies();
            $available = [];
            foreach ($currencies as $currency) {
                $available[$currency] = app(CashCustody::class)->available((int) $actor->id, $currency);
            }
            $school = app(CurrentSchool::class)->id();
            $permission = DB::table('permissions')->where('name', CashupPolicy::MANAGE)->where('guard_name', 'web')->value('id');
            $roles = DB::table('model_has_roles as mr')->join('role_has_permissions as rp', 'rp.role_id', '=', 'mr.role_id')->where('mr.school_id', $school)->where('mr.model_type', $actor->getMorphClass())->where('rp.permission_id', $permission)->pluck('mr.model_id');
            $direct = DB::table('model_has_permissions')->where('school_id', $school)->where('model_type', $actor->getMorphClass())->where('permission_id', $permission)->pluck('model_id');
            $ids = $roles->merge($direct)->unique()->reject(fn ($id) => (int) $id === (int) $actor->id);
            $recipients = [];
            foreach (User::whereIn('id', $ids)->orderBy('id')->get() as $candidate) {
                try {
                    app(CashupPolicy::class)->finance($candidate);
                    $recipients[(int) $candidate->id] = $candidate->first_name.' '.$candidate->last_name;
                } catch (HttpExceptionInterface) {
                    // Only freshly eligible cash operators are offered; no directory disclosure.
                }
            }
            $rows = CashHandover::where(fn ($q) => $q->where('sender_id', $actor->id)->orWhere('recipient_id', $actor->id))->orderByDesc('id')->paginate(30);
            $outcomes = CashHandoverOutcome::whereIn('handover_id', $rows->pluck('id'))->get()->keyBy('handover_id');
            $people = User::withTrashed()->whereIn('id', $rows->pluck('sender_id')->merge($rows->pluck('recipient_id'))->unique())->get()->mapWithKeys(fn ($u) => [(int) $u->id => $u->first_name.' '.$u->last_name])->all();

            return response()->view('money.cashup.handovers', compact('currencies', 'available', 'recipients', 'rows', 'outcomes', 'people'));
        });
    }

    public function propose(CashHandoverPanelRequest $request): Response
    {
        app(CashCustody::class)->propose($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('cashups.handovers.index')->with('success', __('Cash handover proposed. The recipient must confirm the actual cash received.'));
    }

    public function finish(CashHandoverPanelRequest $request, int $handover, string $status): Response
    {
        app(CashCustody::class)->finish($request->user(), $handover, $status, $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('cashups.handovers.index')->with('success', __('The original cash handover outcome has been retained.'));
    }
}
