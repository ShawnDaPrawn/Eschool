<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorOperatorAction extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_operator_actions';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['private_reason'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['operator_id' => 'integer', 'revision' => 'integer', 'actor_id' => 'integer', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
