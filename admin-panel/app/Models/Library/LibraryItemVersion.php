<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of a library item's content (Action Plan 3.3, SA.16). Content
 * changes only while the version is a draft. After that the only changes are
 * the review steps: draft to in review, in review to approved or rejected,
 * approved to published. A published version never changes again, and no
 * version is ever deleted: a learner's practice set may point at it.
 *
 * @property array<string, mixed> $content
 * @property list<int>|null $editor_ids
 * @property string $origin
 */
class LibraryItemVersion extends Model
{
    use PlatformLibraryRow;

    public const STEPS = ['draft' => ['in_review'], 'in_review' => ['approved', 'rejected'], 'approved' => ['published'], 'rejected' => [], 'published' => []];

    /**
     * Who wrote the content: platform staff, an AI draft that a human reviewer
     * must still approve (Action Plan 3.4), or a teacher's question shared by
     * their school (Action Plan 5.4), reviewed the same way.
     */
    public const ORIGINS = ['staff' => 'Written by staff', 'ai_draft' => 'AI draft', 'contributed' => 'Shared by a school'];

    protected $table = 'library_item_versions';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $from = (string) $version->getOriginal('state');
            // Where content came from is fixed when the version is made: an AI draft stays labelled as one, however much staff edit it.
            abort_if($version->isDirty(['origin', 'library_ai_draft_id']), 409, 'A library version keeps the origin it was made with.');
            abort_if($from === 'published', 409, 'A published library version is immutable. Edit the item to start a new draft.');
            abort_if($from !== 'draft' && $version->isDirty(['content', 'estimated_seconds', 'library_item_id', 'number', 'created_by', 'editor_ids']), 409, 'Only a draft library version can change its content.');
            if ($version->isDirty('state')) {
                abort_unless(in_array($version->state, self::STEPS[$from] ?? [], true), 409, 'That review step is not allowed from here.');
            }
        });
        static::deleting(fn () => abort(409, 'Library versions are kept: learners\' practice may point at them.'));
    }

    protected function casts(): array
    {
        return ['content' => 'array', 'editor_ids' => 'array', 'library_item_id' => 'integer', 'number' => 'integer', 'estimated_seconds' => 'integer',
            'library_ai_draft_id' => 'integer', 'submitted_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }

    /**
     * Everyone who wrote or changed this version's content, including what it
     * carried forward from the version before. None of them may review it.
     *
     * @return list<int>
     */
    public function editors(): array
    {
        return array_values(array_unique(array_map('intval', [(int) $this->created_by, ...(array) ($this->editor_ids ?? [])])));
    }

    public function aiDraft(): BelongsTo
    {
        return $this->belongsTo(LibraryAiDraft::class, 'library_ai_draft_id');
    }

    public function drafted(): bool
    {
        return $this->origin === 'ai_draft';
    }

    public function contributed(): bool
    {
        return $this->origin === 'contributed';
    }

    /** The practice question a learner answers (practice and past-paper questions only). */
    public function practiceDefinition(): ?array
    {
        $definition = $this->content['definition'] ?? null;

        return is_array($definition) ? $definition : null;
    }
}
