<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DigestLanguageTemplate extends Model
{
    use Auditable, BelongsToSchool;

    protected $hidden = ['title_template', 'body_template', 'action_templates', 'notice_prompt'];

    protected $guarded = ['id', 'school_id'];

    // Match the database default in the immediate create receipt, before a
    // fresh read; clients must receive revision 1 for their first review.
    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['title_template' => 'encrypted', 'body_template' => 'encrypted', 'action_templates' => 'encrypted:array', 'notice_prompt' => 'encrypted', 'approved_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'version' => 'integer', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $template): void {
            if ($template->isDirty(['language', 'version', 'title_template', 'body_template', 'action_templates', 'notice_prompt', 'content_hash', 'created_by'])) {
                throw new \LogicException('Digest template text is versioned; create another version.');
            }
        });
    }
}
