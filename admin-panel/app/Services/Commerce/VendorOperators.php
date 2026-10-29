<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOperatorAction;
use App\Models\Platform\Role;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class VendorOperators
{
    public function provision(User $actor, array $input): User
    {
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:191'], 'initial_password' => ['required', 'string', Password::defaults(), 'max:72'],
            'confirmed_operator_identity' => ['required', 'accepted'],
        ])->validate();
        $data['email'] = strtolower(trim($data['email']));
        // The operation fingerprint binds the supplied secret without retaining it.
        $fingerprint = $data;
        $fingerprint['initial_password'] = hash_hmac('sha256', $data['initial_password'], (string) config('app.key'));
        $receipt = app(VendorOperations::class)->once($actor, 'operator_provision', $fingerprint, function () use ($data): array {
            abort_if(DB::table('users')->where('email', $data['email'])->lockForUpdate()->exists(), 422, 'This address already belongs to an account. Review its existing identity separately.');
            $user = new User;
            $user->forceFill(['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'], 'password' => Hash::make($data['initial_password']), 'status' => 1, 'password_change_required' => true])->save();
            $role = Role::where('name', 'Vendor')->where('guard_name', 'web')->firstOrFail();
            $user->assignRole($role);
            app(MembershipService::class)->grant($user, app(CurrentSchool::class)->id(), 'Vendor');

            return ['user_id' => (int) $user->id];
        });

        return User::findOrFail($receipt['user_id']);
    }

    public function bind(User $actor, array $input): VendorOperator
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'vendor_id' => ['required', 'integer', 'min:1'], 'user_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'], 'status' => ['required', Rule::in(['active', 'revoked'])], 'private_reason' => ['required', 'string', 'min:3', 'max:2000'], 'confirmed_operator_identity' => ['required', 'accepted']])->validate();
        foreach (['vendor_id', 'user_id', 'revision'] as $field) {
            $data[$field] = (int) $data[$field];
        }
        $authorize = fn () => app(VendorPolicy::class)->finance($actor, $data['status'] !== 'revoked');
        $receipt = app(VendorOperations::class)->once($actor, 'operator_access', $data, function () use ($actor, $data): array {
            $vendor = Vendor::lockForUpdate()->findOrFail($data['vendor_id']);
            $user = User::withTrashed()->lockForUpdate()->findOrFail($data['user_id']);
            $binding = VendorOperator::where('vendor_id', $vendor->id)->where('user_id', $user->id)->lockForUpdate()->first();
            // Revocation remains possible after the operator's school role ends.
            if ($data['status'] === 'active') {
                abort_unless((int) $user->status === 1 && $user->deleted_at === null, 422);
                abort_unless(app(WalletPolicy::class)->roles($user) === ['Vendor'], 422, 'Assign a dedicated current Vendor identity; staff and family accounts are not converted.');
                abort_if(DB::table('platform_staff_identities')->where('user_id', $user->id)->where('active', true)->exists(), 403);
            }
            $latest = $binding === null ? null : VendorOperatorAction::where('operator_id', $binding->id)->orderByDesc('revision')->lockForUpdate()->first();
            abort_unless(($latest === null ? 0 : $latest->revision) === $data['revision'], 409, 'Vendor access changed. Review its current revision.');
            abort_if($binding === null && $data['status'] === 'revoked', 422);
            $binding ??= VendorOperator::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            VendorOperatorAction::create(['operator_id' => $binding->id, 'revision' => $data['revision'] + 1, 'status' => $data['status'], 'actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);

            return ['id' => (int) $binding->id];
        }, $authorize);

        return VendorOperator::findOrFail($receipt['id']);
    }
}
