<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\PlatformSettings;
use App\Services\Platform\Tenancy\SchoolBranding;
use App\Services\Platform\Tenancy\SettingsBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * A settings row: the current school's, or the platform's when school_id is
 * null (see PlatformSettings). Queries see the current school's rows and the
 * platform keys; a new row is the current school's unless its key is a
 * platform key.
 *
 * @property int $id
 * @property int|null $school_id
 * @property string $type
 * @property string|null $message
 */
class Settings extends Model
{
    use Auditable, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'message',
    ];

    /**
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): SettingsBuilder
    {
        return new SettingsBuilder($query);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new PlatformSettings);

        static::creating(function (Settings $setting) {
            if (PlatformSettings::isPlatformKey($setting->type)) {
                $setting->school_id = null;
            } elseif ($setting->school_id === null && ! app(CurrentSchool::class)->isMigrating()) {
                $setting->school_id = app(CurrentSchool::class)->id();
            }
        });

        // The school's row follows its name, crest, timezone and colours.
        static::saved(function (Settings $setting) {
            if ($setting->school_id !== null && SchoolBranding::isBrandingKey($setting->type) && ! app(CurrentSchool::class)->isMigrating()) {
                SchoolBranding::sync((int) $setting->school_id);
            }
        });
    }

    /**
     * Some settings rows still hold secrets (mail password, gateway keys, the
     * service account path); the audit log keeps that they changed, not the
     * value.
     */
    protected function auditRedact(array $values): array
    {
        $type = (string) ($values['type'] ?? $this->getAttribute('type') ?? '');
        if (array_key_exists('message', $values) && preg_match('/pass|secret|key|token|private|credential|signature|service_account/i', $type)) {
            $values['message'] = '[redacted]';
        }

        return $values;
    }
}
