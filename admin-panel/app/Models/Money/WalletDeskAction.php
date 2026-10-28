<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $topup_id
 * @property int $actor_id
 * @property int $ledger_entry_id
 * @property string $action
 * @property bool $requires_second_approval
 * @property string $note
 */
class WalletDeskAction extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['note'];

    protected function casts(): array
    {
        return ['topup_id' => 'integer', 'actor_id' => 'integer', 'ledger_entry_id' => 'integer', 'requires_second_approval' => 'boolean', 'created_at' => UtcInstant::class, 'note' => 'encrypted'];
    }
}
