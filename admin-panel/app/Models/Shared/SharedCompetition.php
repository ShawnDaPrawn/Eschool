<?php

declare(strict_types=1);

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

/**
 * A league or competition between schools. Global (no school_id) and naming
 * no school: members are linked through their own shared_participants rows.
 * The kind is the seam for other competition types (config/shared_records.php).
 */
class SharedCompetition extends Model
{
    public const RECORD = 'competition';

    protected $table = 'shared_competitions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['points_win' => 'integer', 'points_draw' => 'integer', 'points_loss' => 'integer', 'active' => 'boolean', 'revision' => 'integer'];
    }
}
