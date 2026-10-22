<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineExamQuestionAnswer extends Model
{
    use BelongsToSchool, HasFactory;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function question_option()
    {
        return $this->belongsTo(OnlineExamQuestionOption::class, 'answer');
    }

    public function options()
    {
        return $this->belongsTo(OnlineExamQuestionOption::class, 'answer');
    }
}
