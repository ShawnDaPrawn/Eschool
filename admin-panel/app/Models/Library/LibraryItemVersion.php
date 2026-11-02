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
 */
class LibraryItemVersion extends Model
{
    use PlatformLibraryRow;

    public const STEPS = ['draft' => ['in_review'], 'in_review' => ['approved', 'rejected'], 'approved' => ['published'], 'rejected' => [], 'published' => []];

    protected $table = 'library_item_versions';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $from = (string) $version->getOriginal('state');
            abort_if($from === 'published', 409, 'A published library version is immutable. Edit the item to start a new draft.');
            abort_if($from !== 'draft' && $version->isDirty(['content', 'estimated_seconds', 'library_item_id', 'number', 'created_by']), 409, 'Only a draft library version can change its content.');
            if ($version->isDirty('state')) {
                abort_unless(in_array($version->state, self::STEPS[$from] ?? [], true), 409, 'That review step is not allowed from here.');
            }
        });
        static::deleting(fn () => abort(409, 'Library versions are kept: learners\' practice may point at them.'));
    }

    protected function casts(): array
    {
        return ['content' => 'array', 'library_item_id' => 'integer', 'number' => 'integer', 'estimated_seconds' => 'integer',
            'submitted_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }

    /** The practice question a learner answers (practice and past-paper questions only). */
    public function practiceDefinition(): ?array
    {
        $definition = $this->content['definition'] ?? null;

        return is_array($definition) ? $definition : null;
    }
}
