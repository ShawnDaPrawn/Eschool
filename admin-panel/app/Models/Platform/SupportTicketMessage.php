<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One message on a support ticket: the school's or HQ's free text, encrypted
 * at rest. An internal message is an HQ note the school never reads.
 */
class SupportTicketMessage extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'school_support_ticket_messages';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'internal' => 'boolean', 'ticket_id' => 'integer', 'author_id' => 'integer', 'created_at' => 'datetime'];
    }
}
