<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/** One prerequisite edge of the library graph: the topic needs the prerequisite first. */
class LibraryTopicPrerequisite extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_topic_prerequisites';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['library_topic_id' => 'integer', 'prerequisite_id' => 'integer'];
    }
}
