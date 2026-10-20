<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Parents extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function (Parents $parent) {
            if ($parent->isForceDeleting() || empty($parent->email)) {
                return;
            }

            $mutatedEmail = User::buildDeletedEmailPlaceholder($parent->email, $parent->id);
            DB::table('parents')->where('id', $parent->id)->update(['email' => $mutatedEmail]);
            $parent->email = $mutatedEmail;
        });
    }

    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'mobile',
        'image',
        'dob',
        'occupation',
        'dynamic_fields',

    ];
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    protected $appends = ['full_name'];
    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fatherRelationChild()
    {
        return $this->hasMany(Students::class, 'father_id');
    }

    public function motherRelationChild()
    {
        return $this->hasMany(Students::class, 'mother_id');
    }

    public function guardianRelationChild()
    {
        return $this->hasMany(Students::class, 'guardian_id');
    }

    /**
     * Students this parent is the father, mother or guardian of. One query with OR:
     * the old UNION only applied a chained ->where() to its father branch.
     */
    public function children()
    {
        return Students::query()->where(function ($query) {
            $query->where('father_id', $this->id)
                ->orWhere('mother_id', $this->id)
                ->orWhere('guardian_id', $this->id);
        });
    }

    //Getter Attributes
    public function getImageAttribute($value)
    {
        return url(Storage::url($value));
    }

    public function getFullNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }
}
