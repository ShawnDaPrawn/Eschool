<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionMark extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'question_marks';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['mark_units' => 'integer'];
    }

    public function mark()
    {
        return $this->belongsTo(AssessmentMark::class, 'assessment_mark_id');
    }

    public function question()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
}
