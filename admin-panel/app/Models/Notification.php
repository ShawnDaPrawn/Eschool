<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use BelongsToSchool, HasFactory;
    use SoftDeletes;

    // The layered columns stay out of the v1 lists: the body is read only
    // through the freshly checked v2 detail (NotificationInbox::show).
    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'preferences_enforced', 'delivery_payload', 'source_key', 'excerpt', 'body', 'target'];

    protected function casts(): array
    {
        return ['delivery_payload' => 'array', 'preferences_enforced' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Notification $notice) {
            if (! app(CurrentSchool::class)->isMigrating()) {
                $notice->preferences_enforced = true;
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_notifications');
    }
}
