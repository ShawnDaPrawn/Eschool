<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;

/** A human's own choices across schools; school mandatory rules still win. */
class NotificationPreference extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
