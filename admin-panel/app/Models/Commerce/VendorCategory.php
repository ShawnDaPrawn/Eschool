<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorCategory extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_categories';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'changed_by' => 'integer'];
    }
}
