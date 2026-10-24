<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContentItem extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['request_hash'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'revision' => 'integer', 'notice_after_learner_id' => 'integer', 'notice_completed_at' => 'datetime'];
    }

    public function published()
    {
        return $this->belongsTo(ContentVersion::class, 'published_version_id');
    }

    public function draft()
    {
        return $this->belongsTo(ContentVersion::class, 'draft_version_id');
    }

    public function audienceGroups()
    {
        return $this->hasMany(ContentItemGroup::class);
    }

    public function versions()
    {
        return $this->hasMany(ContentVersion::class);
    }
}
