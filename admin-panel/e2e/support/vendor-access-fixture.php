<?php

declare(strict_types=1);

use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOperatorAction;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Commerce\VendorOperators;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Synthetic isolated schema only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if ($action === 'revoke') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function (): array {
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
        $binding = VendorOperator::orderBy('id')->firstOrFail();
        $latest = VendorOperatorAction::where('operator_id', $binding->id)->orderByDesc('revision')->firstOrFail();
        app(VendorOperators::class)->bind($finance, ['operation_id' => (string) Str::uuid(), 'vendor_id' => $binding->vendor_id, 'user_id' => $binding->user_id, 'revision' => $latest->revision, 'status' => 'revoked', 'private_reason' => 'Synthetic operator no longer assigned to this counter', 'confirmed_operator_identity' => true]);

        return ['revoked' => true];
    });
}
$data = require base_path('e2e/support/vendor-orders-fixture.php');
if (in_array($action, ['stats', 'disable'], true)) {
    return $data;
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $email = 'vendor.'.$data['school'].'@demo.navigateed.test';
    $operator = app(VendorOperators::class)->provision($finance, ['operation_id' => (string) Str::uuid(), 'first_name' => 'Synthetic', 'last_name' => 'Counter operator', 'email' => $email, 'initial_password' => 'SyntheticVendor2026', 'confirmed_operator_identity' => true]);
    $operator->forceFill(['password_change_required' => false])->save();
    ParentPolicyService::acceptCurrent($operator);
    app(VendorOperators::class)->bind($finance, ['operation_id' => (string) Str::uuid(), 'vendor_id' => $data['vendor'], 'user_id' => $operator->id, 'revision' => 0, 'status' => 'active', 'private_reason' => 'Synthetic verified operator for one assigned counter', 'confirmed_operator_identity' => true]);

    return $data + ['operator_email' => $email, 'operator_id' => $operator->id, 'operator_password' => 'SyntheticVendor2026', 'school_slug' => School::findOrFail($data['school'])->slug];
});
