<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Cards\CardReaderDevice;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCardReader;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardOperations;
use App\Services\Cards\CardReaders;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

/** A payment device never borrows a gate or classroom device's authority. */
final class VendorPaymentReaders
{
    public function register(User $actor, int $vendor, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:100'], 'public_key' => ['sometimes', 'string', 'max:64'], 'days' => ['required', 'integer', 'between:1,30']])->validate() + ['vendor_id' => $vendor];
        if (isset($data['public_key'])) {
            app(CardReaders::class)->validateEncoderPublicKey($data['public_key']);
        }
        $authority = function () use ($actor, $vendor): void {
            WalletCounterPayments::available(true);
            app(VendorPolicy::class)->finance($actor, true);
            app(CardAccess::class)->manage($actor, true);
            Vendor::where('status', 'active')->findOrFail($vendor);
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'reader.vendor', $data, $authority, function () use ($actor, $data, $vendor): array {
            $secret = bin2hex(random_bytes(32));
            // The shared reader registry requires a public key. Online payment
            // readers receive no offline manifest; authentication uses their
            // purpose-bound transport credential, not this reserved metadata.
            $keypair = sodium_crypto_box_keypair();
            $public = $data['public_key'] ?? base64_encode(sodium_crypto_box_publickey($keypair));
            sodium_memzero($keypair);
            $device = CardReaderDevice::create(['issued_by' => $actor->id, 'name' => $data['name'], 'kind' => 'vendor', 'public_key' => $public, 'secret_hash' => hash('sha256', $secret), 'expires_at' => now('UTC')->addDays((int) $data['days'])]);
            VendorCardReader::create(['vendor_id' => $vendor, 'device_id' => $device->id, 'actor_id' => $actor->id, 'created_at' => now('UTC')]);

            return ['id' => $device->id, 'vendor_id' => $vendor, 'credential' => 'NED1.'.app(CurrentSchool::class)->id().'.'.$device->id.'.'.$secret, 'expires_at' => $device->expires_at->toIso8601String()];
        });
    }

    public function authenticate(User $actor, int $vendor, string $credential, bool $write = true): CardReaderDevice
    {
        WalletCounterPayments::available($write);
        app(VendorOperatorAccess::class)->counter($actor, $vendor, $write);
        $device = app(CardReaders::class)->authenticate($credential, 'vendor', $write);
        app(VendorPolicy::class)->finance(User::findOrFail($device->issued_by), $write);
        abort_unless(VendorCardReader::where('vendor_id', $vendor)->where('device_id', $device->id)->exists(), 403);

        return $device;
    }
}
