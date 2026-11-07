<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Whether a person has proved, with a one-time code, that they hold the
 * number they are texted on at this school (Action Plan 2.7). Holds only a
 * hash of the number and a keyed hash of the code. Written only through
 * App\Services\Platform\Notifications\TextNumbers.
 *
 * @property string $source
 * @property string|null $number_hash
 * @property Carbon|null $verified_at
 * @property string|null $code_hash
 * @property Carbon|null $code_expires_at
 * @property int $attempts
 * @property Carbon|null $locked_until
 * @property int $codes_sent
 * @property Carbon|null $codes_window_started_at
 * @property Carbon|null $last_code_sent_at
 */
class MessagingNumber extends Model
{
    use BelongsToSchool;

    public const PARENT = 'parent';

    public const ACCOUNT = 'account';

    protected $table = 'messaging_numbers';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['number_hash', 'code_hash'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'verified_at' => 'datetime',
            'code_expires_at' => 'datetime',
            'attempts' => 'integer',
            'locked_until' => 'datetime',
            'codes_sent' => 'integer',
            'codes_window_started_at' => 'datetime',
            'last_code_sent_at' => 'datetime',
        ];
    }
}
