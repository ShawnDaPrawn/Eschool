<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorCardReader extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_card_readers';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'actor_id' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
