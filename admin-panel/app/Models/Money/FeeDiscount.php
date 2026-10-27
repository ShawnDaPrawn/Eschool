<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property array $snapshot
 * @property array $credit_note_ids
 * @property int $amount_cents
 */
class FeeDiscount extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['staff_roles' => 'array', 'session_year_id' => 'integer', 'learner_id' => 'integer', 'fee_plan_id' => 'integer', 'fee_item_id' => 'integer', 'amount_value' => 'integer', 'sibling_position' => 'integer', 'minimum_paid_bp' => 'integer', 'independent_approval' => 'boolean', 'revision' => 'integer', 'prepared_by' => 'integer', 'approved_by' => 'integer'];
    }
}
