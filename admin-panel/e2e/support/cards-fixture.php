<?php

declare(strict_types=1);

use App\Models\Cards\Card;
use App\Models\Cards\CardPortrait;
use App\Models\Cards\CardScanLog;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\Cards;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Card browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $learner = Students::where('admission_no', 'DEMO-A-0001')->firstOrFail();
    $action = getenv('CARD_FIXTURE_ACTION') ?: 'initial';
    if ($action === 'proof') {
        $id = (string) getenv('CARD_FIXTURE_ID');
        abort_unless(Str::isUuid($id), 422);

        return app(Cards::class)->credential($parent, $id);
    }
    if ($action === 'scan_count') {
        return ['count' => CardScanLog::where('card_id', getenv('CARD_FIXTURE_ID'))->count()];
    }
    if ($action === 'freeze') {
        return app(Cards::class)->freeze($admin, (string) getenv('CARD_FIXTURE_ID'), ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic browser withdrawal']);
    }
    app(ModuleGate::class)->change('cards', true, $admin);
    // Retain prior synthetic histories while clearing this repeatable browser fixture's current identity.
    Card::where('user_id', $learner->user_id)->whereNotNull('active_identity')->update(['status' => 'replaced', 'active_identity' => null]);
    $parent->forceFill(['email_verified_at' => now()])->save();
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => schoolSetting('session_year')], ['class_section_id' => $learner->class_section_id, 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString()]);
    GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
    app(ApprovalService::class)->decide($parent, $learner, 'approve', config('identity.consent_version'));
    // Generated JPEG only: no actual child's portrait or scanner qualification is used.
    if (! CardPortrait::where('user_id', $learner->user_id)->exists()) {
        app()->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-browser-fixture', 'signature' => null];
            }
        });
        app(CardPortraits::class)->upload($admin, (int) $learner->user_id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('synthetic-portrait.jpg', 120, 150));
    }

    return ['person' => (int) $learner->user_id, 'name' => $learner->user->full_name,
        'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair()))];
});
