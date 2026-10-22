<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSubject extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'subject_id',
        'class_section_id',
        'semester_id',
        'session_year_id',
    ];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
