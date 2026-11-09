<?php

declare(strict_types=1);

namespace App\Models\SignIn;

use App\Models\User;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A Google or Microsoft account linked to one NavigateEd account at one
 * school (Action Plan 5.9). Sign-in matches the provider's stable subject
 * (Google sub, Microsoft oid with its tid), never the email address.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $tenant_id
 * @property string $subject
 * @property string $email_at_link
 * @property string $linked_via
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 */
class SignInProviderLink extends Model
{
    use BelongsToSchool;

    protected $table = 'sign_in_provider_links';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['subject', 'tenant_id'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A short, one-way reference to the provider account for the audit log. */
    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->provider.'|'.$this->tenant_id.'|'.$this->subject), 0, 16);
    }
}
