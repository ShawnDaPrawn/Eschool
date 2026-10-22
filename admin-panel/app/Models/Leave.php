<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'leave_master_id',
        'reason',
        'from_date',
        'to_date',
        'status',
        'session_year_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get all of the leave_detail for the Leave
     *
     * @return HasMany
     */
    public function leave_detail()
    {
        return $this->hasMany(LeaveDetail::class);
    }

    /**
     * Get the leave_master that owns the Leave
     *
     * @return BelongsTo
     */
    public function leave_master()
    {
        return $this->belongsTo(LeaveMaster::class);
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    /**
     * Approved leaves overlapping the given date, mapped as [user_id => leave-day type].
     * The type is one of "Full", "First Half", "Second Half" and is used to lock the
     * matching attendance entry for that day.
     */
    public static function approvedTypesForDate(string $date): array
    {
        return self::where('status', 1)
            ->whereHas('leave_detail', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->with(['leave_detail' => function ($query) use ($date) {
                $query->whereDate('date', $date);
            }])
            ->get()
            ->mapWithKeys(function ($leave) {
                return [$leave->user_id => $leave->leave_detail->first()->type ?? 'Full'];
            })
            ->toArray();
    }

    protected static function booted()
    {
        static::deleting(function ($leave) {

            // Delete all leave details
            $leave->leave_detail()->delete();

            // Delete all files attached via morphMany
            $leave->file()->delete();
        });
    }
}
