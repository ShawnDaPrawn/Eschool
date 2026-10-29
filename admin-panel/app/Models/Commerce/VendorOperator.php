<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorOperator extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_operators';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'user_id' => 'integer', 'created_by' => 'integer', 'created_at' => UtcInstant::class];
    }
}
