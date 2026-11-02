<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\People\Profiles\ProfileDefaults;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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

    /** @var array{key: string, version: int, products: list<string>}|null */
    private ?array $membershipPackage = null;

    /** Immutable applied package modules read with the authenticated memberships. */
    public function withMembershipPackage(string $key, int $version, array $products): void
    {
        $this->membershipPackage = ['key' => $key, 'version' => $version, 'products' => $products];
    }

    /** @return list<string>|null Null when a fresh or changed school needs a database read. */
    public function membershipPackage(string $key, int $version): ?array
    {
        return $this->membershipPackage !== null
            && $this->membershipPackage['key'] === $key && $this->membershipPackage['version'] === $version
                ? $this->membershipPackage['products'] : null;
    }

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';

    public const ARCHIVED = 'archived';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::created(function (School $school) {
            if (! app(CurrentSchool::class)->isMigrating() && Schema::hasTable('profile_field_categories')) {
                ProfileDefaults::seed((int) $school->id);
            }
        });
    }

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
