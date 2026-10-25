<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferPackage;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

/** Revoke old consent-request inbox rows before pagination or body retrieval. */
class TransferNoticeScope implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $fresh = app(TransferAuthority::class)->actor($actor);
        abort_unless($fresh->email_verified_at !== null && in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);
        $school = app(CurrentSchool::class)->id();
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $source = TransferPackage::where('guardian_id', $fresh->id)->where('state', 'awaiting_consent')->where('expires_at', '>', now()->utc())
            ->whereRaw("CAST(transfer_packages.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.transfer_package_id'))")
            ->whereRaw("CAST(transfer_packages.revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.transfer_revision'))")
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)
                ->whereColumn('guardian_links.learner_id', 'transfer_packages.learner_id')->where('guardian_links.guardian_user_id', $fresh->id)->where('guardian_links.status', 'active')->where('guardian_links.can_approve', true));
        $notifications->where('type', TransferNotices::TYPE)->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [TransferNotices::LINK])->whereExists($source->selectRaw('1')->toBase());
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        return app(TransferNotices::class)->allows($actor, $type, $payload);
    }
}
