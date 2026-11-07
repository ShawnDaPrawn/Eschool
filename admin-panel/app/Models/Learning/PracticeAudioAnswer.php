<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A learner's recorded spoken practice answer (Action Plan 3.11), encrypted in the school's private storage. */
class PracticeAudioAnswer extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'practice_audio_answers';

    protected $guarded = ['school_id'];

    protected $hidden = ['path', 'sha256'];

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'purge_after' => 'immutable_date', 'purged_at' => 'immutable_datetime'];
    }
}
