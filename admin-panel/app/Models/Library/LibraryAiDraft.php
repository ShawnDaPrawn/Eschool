<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One HQ request for AI drafts of library items (Action Plan 3.4): who asked,
 * for which topic and source, the brief, the outcome and the items it
 * produced. The items start as drafts and go through the same review as any
 * other: nothing AI-written is published without a reviewer's approval.
 *
 * @property list<int>|null $item_ids
 */
class LibraryAiDraft extends Model
{
    use PlatformLibraryRow;

    public const STATES = ['pending', 'completed', 'failed', 'held'];

    protected $table = 'library_ai_drafts';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleting(fn () => abort(409, 'AI draft requests are kept: library versions point at them.'));
    }

    protected function casts(): array
    {
        return ['requested_by' => 'integer', 'library_topic_id' => 'integer', 'library_source_id' => 'integer', 'count' => 'integer',
            'platform_ai_usage_id' => 'integer', 'item_ids' => 'array'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LibraryTopic::class, 'library_topic_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LibrarySource::class, 'library_source_id');
    }
}
