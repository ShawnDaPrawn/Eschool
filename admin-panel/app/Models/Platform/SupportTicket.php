<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A school's support ticket to Navigate Ed (Action Plan SA.17). The school
 * reads its own; HQ reads every school's from /saas.
 * engineering/specs/platform/support-desk.md.
 */
class SupportTicket extends Model
{
    use BelongsToSchool;

    /** HQ's English labels; the school panel uses the support_category_*, support_priority_* and support_status_* keys. */
    public const CATEGORY_LABELS = ['question' => 'Question', 'problem' => 'Problem', 'request' => 'Request', 'billing' => 'Billing'];

    public const PRIORITY_LABELS = ['normal' => 'Normal', 'urgent' => 'Urgent'];

    /** HQ's words for each status. */
    public const STATUS_LABELS = ['open' => 'Open', 'waiting_on_school' => 'Waiting on school', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    public const CATEGORIES = ['question', 'problem', 'request', 'billing'];

    public const PRIORITIES = ['normal', 'urgent'];

    public const STATUSES = ['open', 'waiting_on_school', 'resolved', 'closed'];

    /** The statuses that still need Navigate Ed or the school: HQ's queue and the open count. */
    public const ACTIVE = ['open', 'waiting_on_school'];

    protected $table = 'school_support_tickets';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime', 'opened_by' => 'integer', 'assigned_to' => 'integer', 'closed_by' => 'integer'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public function reference(): string
    {
        return self::referenceFor((int) $this->id);
    }

    public static function referenceFor(int $id): string
    {
        return 'SUP-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
