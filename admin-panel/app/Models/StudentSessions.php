<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\People\EnrolmentService;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StudentSessions extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_section_id',
        'session_year_id',
        'previous_session_year_id',
    ];

    /** Keep legacy projection writes and their appended history atomic. */
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    /*
    |--------------------------------------------------------------------------
    | Global Scope: Only sessions for active (non-deleted) students
    |--------------------------------------------------------------------------
    | This is required for application-level queries.
    | DO NOT remove. Use withoutStudentScope() for system deletes.
    */
    protected static function booted()
    {
        static::saved(function (StudentSessions $session) {
            if ($session->wasRecentlyCreated || $session->wasChanged('class_section_id')) {
                app(EnrolmentService::class)->fromLegacy($session);
            }
        });

        static::addGlobalScope('active_student_only', function (Builder $builder) {
            $builder->whereHas('student', function ($q) {
                $q->whereNull('deleted_at');
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function student()
    {
        // withTrashed is IMPORTANT so cascade deletes still work
        return $this->belongsTo(Students::class)->withTrashed();
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id')->withTrashed();
    }

    /*
    |--------------------------------------------------------------------------
    | Global Scope Bypass (SYSTEM USE ONLY)
    |--------------------------------------------------------------------------
    */
    public static function withoutStudentScope()
    {
        return static::withoutGlobalScope('active_student_only');
    }
}
