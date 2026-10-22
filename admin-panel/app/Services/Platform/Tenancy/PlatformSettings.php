<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Settings rows per school (the Build Bible, section 3.1, Settings): a row
 * with a school_id is that school's; a row without one is the platform's,
 * the default beneath every school.
 *
 * The platform's own keys (KEYS: mail server, push project, app versions)
 * always live in platform rows and are shared by every school. Every other
 * key is written for the current school. getSettings() and schoolSetting()
 * read the current school's rows over the platform's.
 *
 * As a model scope (on App\Models\Settings): the current school's rows plus
 * the platform keys, so the panel's settings pages read and write the
 * school's own rows and the shared platform keys, never another school's.
 */
final class PlatformSettings implements Scope
{
    /** Keys only the platform has (school_id always null). */
    public const KEYS = [
        'system_version', 'update_warning_modal', 'learner_dob_password_scan_completed_at',
        'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_send_from',
        'email_configration_verification',
        'sender_id', 'project_id', 'service_account_file',
        'app_link', 'ios_app_link', 'app_version', 'ios_app_version', 'force_app_update', 'app_maintenance',
        'teacher_app_link', 'teacher_ios_app_link', 'teacher_app_version', 'teacher_ios_app_version', 'teacher_force_app_update', 'teacher_app_maintenance',
    ];

    public static function isPlatformKey(?string $type): bool
    {
        return in_array($type, self::KEYS, true);
    }

    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = app(CurrentSchool::class)->scopeId();
        if ($schoolId === null) {
            return;
        }
        $builder->where(fn (Builder $query) => $query
            ->where($model->qualifyColumn('school_id'), $schoolId)
            ->orWhere(fn (Builder $platform) => $platform
                ->whereNull($model->qualifyColumn('school_id'))
                ->whereIn($model->qualifyColumn('type'), self::KEYS)));
    }

    /**
     * Settings by type: the platform's rows, then the school's over them.
     *
     * @return array<string, mixed>
     */
    public static function read(string $type = ''): array
    {
        $schoolId = app(CurrentSchool::class)->settingsSchoolId();
        if (app(CurrentSchool::class)->isMigrating() && ! Schema::hasColumn('settings', 'school_id')) {
            // An old migration, before settings had a school.
            return Settings::withoutGlobalScope(self::class)->when($type !== '', fn (Builder $query) => $query->where('type', $type))
                ->pluck('message', 'type')->all();
        }

        $rows = Settings::withoutGlobalScope(self::class)
            ->where(fn (Builder $query) => $query->whereNull('school_id')->when($schoolId !== null, fn (Builder $q) => $q->orWhere('school_id', $schoolId)))
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->orderByRaw('school_id IS NOT NULL')
            ->orderBy('id')
            ->get(['type', 'message']);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->type] = $row->message;
        }

        return $settings;
    }
}
