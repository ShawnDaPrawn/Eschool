<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Models\Academics\CurriculumTopic;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MasteryTopic extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_topics';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['grade_level_id' => 'integer', 'library_topic_id' => 'integer'];
    }

    public function source()
    {
        return $this->belongsTo(CurriculumTopic::class, 'curriculum_topic_id')->withTrashed();
    }
}
