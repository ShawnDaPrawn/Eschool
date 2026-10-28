<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOperatorAction;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

final class VendorOperatorAccess
{
    public function actor(User $actor, bool $ready = true): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_unless(DB::table('schools')->where('id', $context->id())->lockForUpdate()->first()?->status === 'active', 403);
        $fresh = User::whereKey($actor->id)->where('status', 1)->lockForUpdate()->firstOrFail();
        abort_if(DB::table('platform_staff_identities')->where('user_id', $actor->id)->where('active', true)->lockForUpdate()->exists(), 403);
        $roles = app(WalletPolicy::class)->roles($fresh);
        abort_unless(in_array('Vendor', $roles, true), 403);
        abort_if(in_array('Student', $roles, true), 403);
        if ($ready) {
            abort_if($fresh->password_change_required || app(ParentPolicyService::class)->needsAcceptance($fresh), 403, 'Complete vendor sign-in setup for this school.');
        }
        app(ModuleGate::class)->forget();
        abort_unless(app(ModuleGate::class)->allows('vendors'), 403);

        return $fresh;
    }

    public function ownsSession(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        return ($token instanceof PersonalAccessToken && $token->name === AppTokens::VENDOR)
            || ($request->hasSession() && $request->session()->get('portal_app') === AppTokens::VENDOR);
    }

    public function enforceProfile(Request $request): void
    {
        if (! $this->ownsSession($request)) {
            return;
        }
        abort_unless($request->is('api/v2/vendor/*', 'vendor-counter', 'vendor-counter/*'), 403);
        if (! $request->routeIs('api.v2.vendor.sign-out', 'vendor.sign-out')) {
            $this->actor($request->user());
        }
    }

    public function counter(User $actor, int $vendor, bool $write = false): Vendor
    {
        $fresh = $this->actor($actor);
        $counter = Vendor::lockForUpdate()->findOrFail($vendor);
        $binding = VendorOperator::where('vendor_id', $counter->id)->where('user_id', $fresh->id)->lockForUpdate()->firstOrFail();
        $latest = VendorOperatorAction::where('operator_id', $binding->id)->orderByDesc('revision')->lockForUpdate()->first();
        abort_unless($latest?->status === 'active', 403);
        if ($write) {
            abort_unless(app(ModuleGate::class)->writable('vendors', locked: true) && $counter->status === 'active', 403);
        }

        return $counter;
    }

    public function profile(User $actor): array
    {
        $fresh = $this->actor($actor);
        $counters = [];
        foreach (VendorOperator::where('user_id', $fresh->id)->orderBy('id')->get() as $binding) {
            $latest = VendorOperatorAction::where('operator_id', $binding->id)->orderByDesc('revision')->first();
            if ($latest?->status !== 'active') {
                continue;
            }
            $vendor = Vendor::findOrFail($binding->vendor_id);
            $counters[] = ['id' => (int) $vendor->id, 'name' => $vendor->name, 'currency' => $vendor->currency, 'can_collect' => $vendor->status === 'active' && app(ModuleGate::class)->writable('vendors')];
        }
        abort_if($counters === [], 403, 'No current vendor counters are assigned.');
        $school = app(CurrentSchool::class)->school();

        return ['school_id' => (int) $school->id, 'school_name' => $school->name, 'school_slug' => $school->slug, 'user_id' => (int) $fresh->id, 'name' => trim($fresh->first_name.' '.$fresh->last_name), 'counters' => $counters];
    }
}
