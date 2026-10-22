<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A school on the platform. Every school-owned row carries its id (see
 * App\Services\Platform\Tenancy\SchoolOwnedTables); people belong to it
 * through SchoolMembership.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property string $timezone
 * @property string|null $package_key
 * @property string|null $short_name
 * @property string|null $tagline
 * @property string|null $crest_path
 * @property string|null $theme_color
 * @property string|null $secondary_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class School extends Model
{
    use Auditable;

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';

    public const ARCHIVED = 'archived';

    protected $guarded = ['id'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /** The crest's URL, or null when none is uploaded. */
    public function crestUrl(): ?string
    {
        return $this->crest_path ? url(Storage::url($this->crest_path)) : null;
    }
}
