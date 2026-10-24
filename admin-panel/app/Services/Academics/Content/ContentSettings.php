<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\ContentSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentSettings
{
    public function data(): array
    {
        $row = ContentSetting::first();

        return ['transcription_enabled' => $row?->transcription_enabled ?? true, 'daily_transcription_seconds' => $row?->daily_transcription_seconds ?? (int) config('ai.transcribe.daily_school_seconds'), 'revision' => $row?->revision ?? 0];
    }

    public static function rules(): array
    {
        return ['transcription_enabled' => ['required', 'boolean'], 'daily_transcription_seconds' => ['required', 'integer', 'between:600,28800'], 'revision' => ['required', 'integer', 'min:0']];
    }

    public function save(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(AcademicAuthority::class)->manage($actor);
            $row = ContentSetting::first() ?? new ContentSetting(['revision' => 0]);
            abort_unless((int) $row->revision === (int) $data['revision'], 409, __('Content settings changed. Review before saving.'));
            $before = $row->getAttributes();
            $row->fill(['transcription_enabled' => (bool) $data['transcription_enabled'], 'daily_transcription_seconds' => (int) $data['daily_transcription_seconds'], 'revision' => $row->revision + 1])->save();
            Audit::record('academics.content_settings_saved', $row, $before, $row->getAttributes());

            return $this->data();
        });
    }
}
