<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property string|null $legal_address */
class Vendor extends Model
{
    use BelongsToSchool;

    protected $table = 'vendors';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = ['private_bank_instructions'];

    protected function casts(): array
    {
        return ['ledger_account_id' => 'integer', 'category_id' => 'integer', 'vat_registered' => 'boolean', 'settlement_delay_days' => 'integer', 'revision' => 'integer', 'changed_by' => 'integer', 'private_bank_instructions' => 'encrypted'];
    }
}
