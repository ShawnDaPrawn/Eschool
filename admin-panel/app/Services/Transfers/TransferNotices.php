<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferPackage;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransferNotices
{
    public const TYPE = 'transfer.consent_requested';

    public const LINK = '/academics/transfers';

    public function prepared(TransferPackage $package): void
    {
        $actor = User::findOrFail($package->guardian_id);
        $payload = ['link' => self::LINK, 'transfer_package_id' => (int) $package->id, 'transfer_revision' => (int) $package->revision];
        if (! $this->allows($actor, self::TYPE, $payload)) {
            return;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::TYPE, __('School transfer review'), __('Open School transfers to review a request from your school.'), self::LINK);
        $notice->delivery_payload = $payload;
        $notice->save();
        $notifier->assign($notice, [(int) $actor->id]);
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if ($type !== self::TYPE || ($payload['link'] ?? null) !== self::LINK) {
            return false;
        }
        foreach (['transfer_package_id', 'transfer_revision'] as $key) {
            $value = $payload[$key] ?? null;
            if ((! is_int($value) && ! is_string($value)) || preg_match('/^[1-9][0-9]*$/D', (string) $value) !== 1 || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return false;
            }
        }
        try {
            $p = app(TransferAuthority::class)->ownConsent($actor, (int) $payload['transfer_package_id']);

            return $p->state === 'awaiting_consent' && $p->expires_at->isFuture() && (int) $p->revision === (int) $payload['transfer_revision'];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
