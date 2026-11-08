<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Cards\Card;
use App\Models\Cards\CardReaderDevice;
use App\Models\Cards\CardScanLog;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Platform\Modules\ModuleGate;

/**
 * A recent card scan at this school's gate, used by gate staff to sign in a
 * visitor or check a guardian before a release. Scans are only ever written
 * by the server after it verified the card (CardScans), so a scan id is
 * proof the card was presented at that reader a moment ago.
 */
class EmergencyScans
{
    public const MAX_AGE_SECONDS = 120;

    /** @return array{0: CardScanLog, 1: Card, 2: User} */
    public function guardianCard(string $scanId): array
    {
        abort_unless(app(ModuleGate::class)->allows('cards'), 422, 'Card checks need the ID cards module. Use manual entry instead.');
        $scan = CardScanLog::where('outcome', 'accepted')->whereIn('method', ['qr', 'sun'])->find($scanId);
        abort_if($scan === null, 422, 'That card scan was not found. Scan the card again.');
        $device = CardReaderDevice::where('kind', 'gate')->where('status', 'active')->whereNull('revoked_at')->find($scan->device_id);
        abort_unless($device !== null && $device->expires_at->isFuture(), 422, 'That scan was not made at a gate reader. Scan the card at the gate.');
        abort_unless($scan->scanned_at->lte(now()) && $scan->scanned_at->gte(now()->subSeconds(self::MAX_AGE_SECONDS)), 409, 'That card scan is too old. Scan the card again.');
        $card = Card::find($scan->card_id);
        abort_unless($card !== null && $card->type === 'guardian' && (int) $card->user_id === (int) $scan->user_id, 422, 'Scan a guardian card.');
        $person = app(CardAccess::class)->active($card);

        return [$scan, $card, $person];
    }
}
