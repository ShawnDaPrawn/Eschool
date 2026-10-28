<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $count_id
 * @property string $source_hash
 * @property array<string, int> $watermarks
 */
class CashupCountSource extends Model
{
    use BelongsToSchool;

    protected $table = 'cashup_count_sources';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['count_id' => 'integer', 'watermarks' => 'array', 'created_at' => UtcInstant::class];
    }
}
