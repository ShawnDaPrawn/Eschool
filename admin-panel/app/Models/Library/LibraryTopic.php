<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A platform library topic for one framework, subject code and grade (Action Plan 3.3). */
class LibraryTopic extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_topics';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['library_framework_id' => 'integer', 'grade_level_id' => 'integer'];
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(LibraryFramework::class, 'library_framework_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'library_topic_prerequisites', 'library_topic_id', 'prerequisite_id');
    }
}
