<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Platform\RolePivot;
use App\Models\Platform\SchoolMembership;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\MemberScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    use HasRoles {
        roles as spatieRoles;
    }

    protected string $guard_name = 'web';

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'device_type',
        'fcm_id',
        'password',
        'mobile',
        'image',
        'dob',
        'current_address',
        'permanent_address',
        'status',
        'reset_request',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Sent as its own field at sign-in, not inside every user object.
        'password_change_required',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['full_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_change_required' => 'boolean',
        ];
    }

    protected static function booted()
    {
        // Once a school is chosen, only the people who belong to it.
        static::addGlobalScope(new MemberScope);

        // A new password ends a forced change (learners:flag-dob-passwords),
        // unless the same save sets the flag itself.
        static::saving(function (User $user) {
            if ($user->exists && $user->isDirty('password') && ! $user->isDirty('password_change_required')) {
                $user->password_change_required = false;
            }
        });

        static::deleting(function (User $user) {
            if ($user->isForceDeleting() || empty($user->email)) {
                return;
            }

            $mutatedEmail = static::buildDeletedEmailPlaceholder($user->email, $user->id);
            DB::table('users')->where('id', $user->id)->update(['email' => $mutatedEmail]);
            $user->email = $mutatedEmail;
        });
    }

    // Frees the original email for reuse by mutating it on soft-delete, since `email` carries a hard DB-level unique index.
    public static function buildDeletedEmailPlaceholder(string $email, int|string $id): string
    {
        return substr($email, 0, 190).'::deleted-'.$id.'-'.time();
    }

    /** Learner accounts. Unlike role('Student'), this works before the roles are seeded. */
    public function scopeLearners(Builder $query): Builder
    {
        return $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'Student'));
    }

    /**
     * The user's roles at the current school (Spatie teams). school_id stays
     * out of the pivot's JSON so v1 answers keep their shape.
     */
    public function roles(): BelongsToMany
    {
        return $this->spatieRoles()->using(RolePivot::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    /** Every user, whichever school they belong to: for identity and platform code only. */
    public static function acrossSchools(): Builder
    {
        return static::withoutGlobalScope(MemberScope::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Students::class, 'user_id', 'id');
    }

    public function parent(): HasOne
    {
        return $this->hasOne(Parents::class, 'user_id', 'id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id', 'id');
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'user_id', 'id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'user_id')->with('leave_detail');
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'user_notifications');
    }

    public function messages()
    {
        return $this->morphMany(ChatMessage::class, 'modal');
    }

    // Getter Attributes
    public function getImageAttribute($value): string
    {
        return url(Storage::url($value));
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
