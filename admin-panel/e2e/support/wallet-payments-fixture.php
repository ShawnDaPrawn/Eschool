<?php

declare(strict_types=1);

use App\Models\Cards\CardChipProfile;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\WalletCounterPayment;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorPaymentReaders;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Wallet payment fixtures require isolated synthetic data.');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'pause'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($action): array {
        if ($action === 'pause') {
            $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
            app(ModuleGate::class)->change('wallet_payments', false, $finance);
        }

        return ['payments' => WalletCounterPayment::count(), 'wallet_cents' => app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents, 'on_hand' => VendorStockBalance::sole()->on_hand, 'healthy' => VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()) && app(LedgerService::class)->projectionDifferences() === []];
    });
}
$data = require base_path('e2e/support/vendor-access-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $finance->roles()->firstOrFail()->givePermissionTo([CardAccess::MANAGE, CardAccess::READERS, CardAccess::ENCODE]);
    app(ModuleGate::class)->change('wallet_payments', true, $finance);
    app(ModuleGate::class)->change('cards', true, $finance);
    $setting = WalletSetting::sole();
    app(WalletSettings::class)->save($finance, ['operation_id' => (string) Str::uuid(), 'revision' => $setting->revision, 'currency' => 'ZAR', 'daily_spend_cents' => 25000, 'child_limits_required' => false]);
    $parent = User::findOrFail($data['parents'][0]);
    $wallet = WalletAccount::sole();
    $product = VendorProduct::sole();
    $quote = app(VendorOrders::class)->quote($parent, ['vendor_id' => $data['vendor'], 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => 1]]]);
    $instruction = app(WalletCounterPayments::class)->qr($parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
    // Generated image and known cryptographic test vector; no real person or card.
    app()->bind(UploadScanner::class, fn () => new class implements UploadScanner
    {
        public function scan(string $bytes): array
        {
            return ['clean' => true, 'engine' => 'synthetic-generated-fixture', 'signature' => null];
        }
    });
    app(CardPortraits::class)->upload($finance, $parent->id, 'guardian', (string) Str::uuid(), UploadedFile::fake()->image('synthetic-card.jpg', 120, 160));
    $card = app(Cards::class)->issue($finance, ['operation_id' => (string) Str::uuid(), 'person_id' => $parent->id, 'type' => 'guardian']);
    $public = base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair()));
    $reader = app(VendorPaymentReaders::class)->register($finance, $data['vendor'], ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic payment reader', 'public_key' => $public, 'days' => 1]);
    $encoder = app(CardReaders::class)->register($finance, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic encoder', 'kind' => 'encoder', 'public_key' => $public, 'days' => 1]);
    CardChipProfile::create(['card_id' => $card['id'], 'encoder_device_id' => $encoder['id'], 'uid' => '04DE5F1EACC040', 'uid_hash' => hash('sha256', '04DE5F1EACC040'), 'metadata_key' => bin2hex(str_repeat("\0", 16)), 'file_read_key' => bin2hex(str_repeat("\0", 16)), 'encoding_package' => 'synthetic-fixture-only', 'encoded_at' => now('UTC')]);

    return $data + ['payment_qr' => $instruction['qr'], 'product' => $product->id, 'payment_reader' => $reader['credential'], 'tap_uri' => rtrim(config('app.url'), '/').'/card-tap#s='.$data['school'].'&c='.$card['id'].'&e=EF963FF7828658A599F3041510671E88&m=94EED9EE65337086'];
});
