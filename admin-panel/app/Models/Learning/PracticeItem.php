<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A teacher's approved practice question. It belongs to a subject and grade in
 * its school (its bank), and reaches the learners of each teaching group it is
 * used in (PracticeItemGroup). teaching_group_id is the group it was first
 * written for. engineering/specs/learning/question-banks.md (Action Plan 3.6).
 */
class PracticeItem extends Model
{
    use BelongsToSchool;

    /** Who may find and reuse the question: only the author, the teachers of the groups using it, or every teacher of its subject and grade. */
    public const VISIBILITIES = ['private', 'groups', 'school'];

    protected $table = 'practice_items';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'active' => 'boolean', 'subject_id' => 'integer', 'grade_level_id' => 'integer', 'author_id' => 'integer'];
    }

    /** @return HasMany<PracticeItemGroup, $this> */
    public function uses(): HasMany
    {
        return $this->hasMany(PracticeItemGroup::class, 'practice_item_id');
    }

    /** Questions used in a teaching group: the ones its learners practise. */
    public function scopeUsedIn(Builder $query, int $groupId): Builder
    {
        return $query->whereIn('practice_items.id', PracticeItemGroup::query()->where('teaching_group_id', $groupId)->select('practice_item_id'));
    }
}
