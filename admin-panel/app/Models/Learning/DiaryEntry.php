<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's own note or to-do. title and body are DiaryCipher ciphertext for
 * the owning user; read them only through DiaryEntries.
 */
class DiaryEntry extends Model
{
    use BelongsToSchool;

    protected $guarded = ['school_id'];

    protected $hidden = ['title', 'body', 'operation_id'];

    protected function casts(): array
    {
        return ['entry_on' => 'date', 'remind_at' => 'datetime', 'done_at' => 'datetime', 'revision' => 'integer', 'user_id' => 'integer'];
    }
}
