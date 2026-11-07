<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A book or shelf this school hides from its learners. The catalogue itself is unchanged. School-owned. */
class ReadingHiddenItem extends Model
{
    use BelongsToSchool;

    protected $table = 'reading_hidden_items';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['item_id' => 'integer', 'hidden_by' => 'integer'];
    }
}
