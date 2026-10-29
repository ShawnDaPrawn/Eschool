<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorOperatorPanelRequest;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOperatorAction;
use App\Models\User;
use App\Services\Commerce\VendorOperators;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VendorOperatorPanelController extends Controller
{
    public function index(VendorOperatorPanelRequest $request): Response
    {
        return DB::transaction(function (): Response {
            $vendors = Vendor::orderBy('name')->pluck('name', 'id')->all();
            $operators = User::role('Vendor')->orderBy('last_name')->get()->mapWithKeys(fn ($u) => [$u->id => $u->first_name.' '.$u->last_name.' · '.$u->email])->all();
            $bindings = VendorOperator::orderByDesc('id')->paginate(30);
            $actions = VendorOperatorAction::whereIn('operator_id', $bindings->pluck('id'))->orderBy('revision')->get()->keyBy('operator_id');
            $canWrite = app(ModuleGate::class)->writable('vendors');

            return response()->view('money.vendors.operators', compact('vendors', 'operators', 'bindings', 'actions', 'canWrite'));
        });
    }

    public function provision(VendorOperatorPanelRequest $request): Response
    {
        app(VendorOperators::class)->provision($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('vendor-operators.index')->with('success', __('Dedicated vendor identity created. Assign its counter and share the temporary password privately.'));
    }

    public function bind(VendorOperatorPanelRequest $request): Response
    {
        app(VendorOperators::class)->bind($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('vendor-operators.index')->with('success', __('Vendor counter access revision recorded.'));
    }
}
