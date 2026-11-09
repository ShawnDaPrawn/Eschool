<?php

declare(strict_types=1);

namespace App\Models\Library;

use App\Models\Learning\PracticeItem;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A school's offer of one of its teachers' approved practice questions to the
 * shared library (Action Plan 5.4). School-owned: another school never sees
 * it. It names the question version offered, never a learner, and the
 * library item it became once it left the school. Kept as a record.
 */
class LibraryContribution extends Model
{
    use BelongsToSchool;

    /** awaiting_approval: a school leader must approve it first; shared: it left the school for Navigate Ed's review; declined by a leader; withdrawn. */
    public const STATES = ['awaiting_approval', 'shared', 'declined', 'withdrawn'];

    /** How the published item credits its author, as the teacher chose. */
    public const CREDITS = ['named' => 'My name and my school', 'school' => 'My school only', 'anonymous' => 'A NavigateEd teacher'];

    protected $table = 'library_contributions';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::deleting(fn () => abort(409, 'Contributions are kept as a record of what left the school.'));
    }

    protected function casts(): array
    {
        return ['practice_item_id' => 'integer', 'practice_item_version_id' => 'integer', 'library_topic_id' => 'integer', 'contributed_by' => 'integer',
            'decided_by' => 'integer', 'library_item_id' => 'integer', 'withdrawn_by' => 'integer', 'rights_confirmed_at' => 'datetime', 'decided_at' => 'datetime',
            'shared_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function practiceItem(): BelongsTo
    {
        return $this->belongsTo(PracticeItem::class, 'practice_item_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LibraryTopic::class, 'library_topic_id');
    }

    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }
}
