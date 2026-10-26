<?php

declare(strict_types=1);

namespace App\Models\Activities\Chat;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GroupChatPolicy extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_chat_policies';

    protected $guarded = ['school_id'];

    protected function casts(): array
    {
        return ['staff_learners' => 'boolean', 'staff_guardians' => 'boolean', 'staff_staff' => 'boolean', 'learner_shared_replies' => 'boolean', 'guardian_shared_replies' => 'boolean', 'revision' => 'integer'];
    }
}
