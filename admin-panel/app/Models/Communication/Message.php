<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['title', 'markdown', 'audience', 'decision_reason', 'withdrawal_reason'];

    protected function casts(): array
    {
        return ['title' => 'encrypted', 'markdown' => 'encrypted', 'audience' => 'encrypted:array', 'channels' => 'array',
            'decision_reason' => 'encrypted', 'withdrawal_reason' => 'encrypted', 'requires_approval' => 'boolean',
            'scheduled_at' => 'datetime', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'published_at' => 'datetime',
            'sent_at' => 'datetime', 'withdrawn_at' => 'datetime', 'revision' => 'integer', 'recipient_count' => 'integer',
            'delivery_after_user_id' => 'integer', 'review_after_user_id' => 'integer', 'review_completed_revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (Message $message) {
            if ($message->getRawOriginal('published_at') !== null) {
                foreach (['title', 'markdown', 'audience', 'channels', 'author_id', 'scheduled_at', 'approved_by', 'approved_at', 'publication_id'] as $field) {
                    abort_if($message->isDirty($field), 409, __('Published messages are immutable. Prepare a new message.'));
                }
            }
        });
        static::deleting(fn () => abort(409, __('School message history must be retained.')));
    }
}
