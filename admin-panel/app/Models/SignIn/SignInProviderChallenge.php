<?php

declare(strict_types=1);

namespace App\Models\SignIn;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One sign-in or link attempt (Action Plan 5.9): the hashes of its state and
 * nonce and, for the panel, its PKCE verifier (encrypted). Used once, within
 * a few minutes. See App\Services\Platform\SignIn\ProviderChallenges.
 *
 * @property int $id
 * @property int $school_id
 * @property string $provider
 * @property string $purpose
 * @property string $channel
 * @property int|null $user_id
 * @property string $nonce_hash
 * @property string|null $state_hash
 * @property string|null $code_verifier
 * @property string|null $app
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 */
class SignInProviderChallenge extends Model
{
    use BelongsToSchool;

    public const SIGN_IN = 'sign_in';

    public const LINK = 'link';

    public const WEB = 'web';

    public const APP = 'app';

    protected $table = 'sign_in_provider_challenges';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['nonce_hash', 'state_hash', 'code_verifier'];

    protected function casts(): array
    {
        return ['code_verifier' => 'encrypted', 'expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }
}
