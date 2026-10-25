<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TopicLink extends Model
{
    use BelongsToSchool;

    protected $table = 'topic_links';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [];
    }
}
