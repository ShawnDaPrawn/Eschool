<?php

declare(strict_types=1);
use App\Models\Money\FeePaymentChoice;
use App\Models\Money\FeePlan;
use App\Models\Money\InvoiceLine;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

$data = require base_path('e2e/support/family-billing-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['choices' => FeePaymentChoice::count(), 'scheduled_lines' => InvoiceLine::whereNotNull('installment_due_on')->count()]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $account = app(FeeAccounts::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic scheduled family', 'currency' => 'ZAR', 'session_year_id' => $data['year'],
        'learner_ids' => array_column($data['learners'], 'id'), 'holders' => array_map(fn ($id) => ['user_id' => $id, 'share_percent' => '50'], $data['parents'])]);
    $category = ProfileFieldCategory::create(['profile_type' => 'learner', 'key' => 'campus-rules', 'label' => 'Campus', 'sort_order' => 0]);
    $field = ProfileFieldDefinition::create(['category_id' => $category->id, 'profile_type' => 'learner', 'key' => 'campus', 'label' => 'Campus', 'type' => 'dropdown', 'source' => 'custom', 'options' => ['North', 'South'], 'validation' => [],
        'visible_to' => ['school_admin'], 'editable_by' => ['school_admin'], 'encrypted' => true, 'hidden' => false, 'sensitive' => false, 'required' => false, 'platform_locked' => false, 'sort_order' => 0]);
    foreach (Students::whereIn('id', array_column($data['learners'], 'id'))->get() as $student) {
        ProfileFieldValue::create(['user_id' => $student->user_id, 'definition_id' => $field->id, 'encrypted' => true, 'value' => Crypt::encryptString(json_encode('North'))]);
    }

    return $data + ['account' => (int) $account->id, 'plan' => (int) FeePlan::firstOrFail()->id, 'campus' => (int) $field->id];
});
