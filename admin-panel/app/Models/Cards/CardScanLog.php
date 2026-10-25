<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardScanLog extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'card_scan_logs';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['scanned_at' => UtcInstant::class];
    }
}
