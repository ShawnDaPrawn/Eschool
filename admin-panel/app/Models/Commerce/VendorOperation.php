<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property array $receipt */
class VendorOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_operations';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'receipt' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
