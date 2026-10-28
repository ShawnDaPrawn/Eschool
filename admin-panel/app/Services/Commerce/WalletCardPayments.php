<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\WalletPaymentIntent;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** SUN verifies a new online instruction; an admission or gate scan cannot debit money. */
final class WalletCardPayments
{
    public function prepare(User $actor, int $vendor, string $credential, array $input): array
    {
        app(VendorPaymentReaders::class)->authenticate($actor, $vendor, $credential);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'card_id' => ['required', 'uuid'],
            'picc' => ['required', 'regex:/\A[a-fA-F0-9]{32}\z/D'], 'mac' => ['required', 'regex:/\A[a-fA-F0-9]{16}\z/D'],
            'lines' => ['required', 'array', 'min:1', 'max:20'], 'lines.*.product_id' => ['required', 'integer', 'min:1', 'distinct'], 'lines.*.quantity' => ['required', 'integer', 'between:1,999'],
        ])->validate() + ['vendor_id' => $vendor];
        $device = app(VendorPaymentReaders::class)->authenticate($actor, $vendor, $credential);
        // A registered device and operator scope the replay; raw SUN/reader proofs are not retained.
        $data['device_id'] = $device->id;
        $receipt = app(VendorOperations::class)->once($actor, 'wallet_card_instruction', $data, function () use ($actor, $vendor, $credential, $data): array {
            $authority = CounterSpendAuthority::fromTap($actor, $vendor, $credential, $data);
            $priced = app(VendorOrders::class)->counterQuote($authority);
            $intent = $authority->intent;
            $intent->fill(['currency' => $priced['currency'], 'amount_cents' => $priced['amount_cents'], 'net_cents' => $priced['net_cents'], 'vat_cents' => $priced['vat_cents'],
                'fingerprint' => hash('sha256', json_encode($priced, JSON_THROW_ON_ERROR)),
                'private_quote' => $intent->private_quote + ['review' => array_intersect_key($priced, array_flip(['seller', 'lines']))]]);
            $intent->save();

            return ['kind' => 'wallet_card_instruction', 'vendor_id' => $vendor, 'id' => $intent->id];
        }, fn () => app(VendorPaymentReaders::class)->authenticate($actor, $vendor, $credential));

        return $this->review($actor, $vendor, $credential, $receipt['id']);
    }

    public function review(User $actor, int $vendor, string $credential, string $id): array
    {
        return DB::transaction(function () use ($actor, $vendor, $credential, $id): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $intent = WalletPaymentIntent::where('vendor_id', $vendor)->where('kind', 'sun')->lockForUpdate()->findOrFail($id);
            CounterSpendAuthority::fromIntent($actor, $intent, $credential);

            return app(WalletCounterPayments::class)->counterInstruction($actor, $intent) + ['holder_name' => User::findOrFail($intent->actor_id)->full_name, 'portrait_revision' => $intent->portrait_revision];
        }, 3);
    }

    public function portrait(User $actor, int $vendor, string $credential, string $id): array
    {
        return DB::transaction(function () use ($actor, $vendor, $credential, $id): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $intent = WalletPaymentIntent::where('vendor_id', $vendor)->where('kind', 'sun')->lockForUpdate()->findOrFail($id);
            $authority = CounterSpendAuthority::fromIntent($actor, $intent, $credential);

            return app(CardPortraits::class)->counter($authority);
        }, 3);
    }
}
