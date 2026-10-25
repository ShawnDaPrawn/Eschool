<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['notification_id', 'user_id'];

    protected function casts(): array
    {
        return ['private_read_at' => UtcInstant::class];
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
