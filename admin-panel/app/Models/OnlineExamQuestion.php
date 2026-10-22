<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class OnlineExamQuestion extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function class_subject()
    {
        return $this->belongsTo(ClassSubject::class, 'class_subject_id');
    }

    public function options()
    {
        return $this->hasMany(OnlineExamQuestionOption::class, 'question_id');
    }

    public function answers()
    {
        return $this->hasMany(OnlineExamQuestionAnswer::class, 'question_id')->with('options');
    }

    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        } else {
            return null;
        }
    }

    protected static function booted()
    {
        static::deleting(function ($question) {

            $choices = OnlineExamQuestionChoice::where('question_id', $question->id);

            if ($question->isForceDeleting()) {

                // Delete student answers first (via choices).
                // online_exam_student_answers.question_id references online_exam_question_choices.id
                OnlineExamStudentAnswer::whereIn(
                    'question_id',
                    $choices->pluck('id')
                )->forceDelete();

                // Delete choices
                $choices->forceDelete();
            } else {

                OnlineExamStudentAnswer::whereIn(
                    'question_id',
                    $choices->pluck('id')
                )->delete();

                $choices->delete();
            }
        });
    }
}
