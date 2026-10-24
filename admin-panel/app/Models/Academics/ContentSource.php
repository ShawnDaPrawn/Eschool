<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContentSource extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['name', 'path', 'sha256'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'bytes' => 'integer', 'scanned_at' => 'datetime'];
    }

    public function item()
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    protected static function booted(): void
    {
        static::updating(function (ContentSource $row) {
            abort_unless(array_diff(array_keys($row->getDirty()), ['scan_state', 'scan_engine', 'scan_signature', 'scanned_at', 'updated_at']) === [], 409, __('Source files are immutable. Upload a new version.'));
        });
        static::deleting(fn () => abort(409, __('Content sources must be retained.')));
    }
}
