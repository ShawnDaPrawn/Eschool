<?php

declare(strict_types=1);

namespace App\Services\School;

use App\Models\Platform\School;
use App\Models\Semester;
use App\Models\Settings;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\SchoolProfile;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/** School publishing only: contact details, branding and public policy PDFs. */
class SchoolInformation
{
    public const FIELDS = ['school_name', 'school_short_name', 'school_tagline', 'school_website', 'school_phone', 'school_email', 'school_address', 'time_zone'];

    public function authorise(User $actor, bool $edit = false): User
    {
        // Reuse the fresh active-membership authority used by person profiles;
        // an expired role still attached in Spatie must not grant publishing.
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if($edit && ! $policy->permission($fresh, 'setting-create'), 403);

        return $fresh;
    }

    public static function rules(): array
    {
        return [
            'school_name' => ['sometimes', 'required', 'string', 'max:255'],
            'school_short_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'school_tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_website' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'school_phone' => ['sometimes', 'nullable', 'string', 'max:80'],
            'school_email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'school_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'time_zone' => ['sometimes', 'required', 'timezone:all'],
            'crest' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public static function documentRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(['code_of_conduct', 'fee_schedule', 'discipline_framework', 'other'])],
            'document' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:5120'],
        ];
    }

    public function settings(): array
    {
        return Settings::query()->where('school_id', app(CurrentSchool::class)->id())->whereIn('type', self::FIELDS)
            ->orderBy('id')->pluck('message', 'type')->all();
    }

    private function documents(): array
    {
        $json = Settings::query()->where('school_id', app(CurrentSchool::class)->id())->where('type', 'school_documents')->value('message');
        $documents = $json === null ? [] : json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($documents)) {
            throw new RuntimeException('School document metadata is unavailable.');
        }

        return $documents;
    }

    public function info(User $actor, int $schoolId): array
    {
        $this->authorise($actor);
        abort_unless($schoolId === app(CurrentSchool::class)->id(), 404);
        $school = School::query()->active()->findOrFail($schoolId);
        $profile = SchoolProfile::current($school);
        $settings = $this->settings();
        $value = fn ($key) => trim((string) ($settings[$key] ?? '')) === '' ? null : $settings[$key];
        $terms = $profile->sessionYearId === null ? collect() : Semester::query()->where('session_year_id', $profile->sessionYearId)
            ->orderBy('start_date')->orderBy('id')->get();

        return [
            'school' => ['id' => $profile->id, 'name' => $profile->name, 'short_name' => $profile->shortName, 'crest_url' => $profile->crestUrl],
            'website' => $value('school_website'), 'phone' => $value('school_phone'), 'email' => $value('school_email'), 'address' => $value('school_address'),
            'documents' => array_map(fn ($document) => [
                'id' => $document['id'], 'title' => $document['title'], 'kind' => $document['kind'],
                'url' => url(Storage::disk('public')->url($document['path'])),
            ], $this->documents()),
            'term_dates' => $terms->map(fn ($term) => ['name' => $term->name, 'starts_on' => $term->start_date?->toDateString(), 'ends_on' => $term->end_date?->toDateString()])->all(),
        ];
    }

    public function update(User $actor, array $input, ?UploadedFile $crest): void
    {
        $this->authorise($actor, edit: true);
        $data = Validator::make($input + ($crest === null ? [] : ['crest' => $crest]), self::rules())->validate();
        $path = null;
        try {
            if ($crest !== null) {
                $path = UploadService::upload($crest, 'school-branding');
                if (! Storage::disk('public')->exists($path)) {
                    throw new RuntimeException('The crest could not be stored.');
                }
            }
            DB::transaction(function () use ($actor, $data, $path) {
                $school = School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
                $this->authorise($actor, edit: true);
                foreach (self::FIELDS as $key) {
                    if (array_key_exists($key, $data)) {
                        Settings::query()->updateOrCreate(['type' => $key], ['message' => $data[$key] ?? '']);
                    }
                }
                if ($path !== null) {
                    Settings::query()->updateOrCreate(['type' => 'logo1'], ['message' => $path]);
                }
                Audit::record('school.information_updated', $school, [], ['fields' => array_values(array_intersect(self::FIELDS, array_keys($data))), 'crest_changed' => $path !== null], schoolId: $school->id);
                Outbox::record(new ContextChanged($school->id, 'school.information'));
            });
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }
        // Keep the previous crest: it can still be referenced by cached clients.
    }

    public function publish(User $actor, array $input): void
    {
        $this->authorise($actor, edit: true);
        $data = Validator::make($input, self::documentRules())->validate();
        $path = UploadService::upload($data['document'], 'school-documents');
        if (! Storage::disk('public')->exists($path)) {
            throw new RuntimeException('The document could not be stored.');
        }
        try {
            DB::transaction(function () use ($actor, $data, $path) {
                $school = School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
                $this->authorise($actor, edit: true);
                $documents = $this->documents();
                $id = (string) Str::uuid();
                $documents[] = ['id' => $id, 'title' => $data['title'], 'kind' => $data['kind'], 'path' => $path];
                Settings::query()->updateOrCreate(['type' => 'school_documents'], ['message' => json_encode($documents, JSON_THROW_ON_ERROR)]);
                Audit::record('school.document_published', $school, [], ['document_id' => $id, 'kind' => $data['kind']], schoolId: $school->id);
                Outbox::record(new ContextChanged($school->id, 'school.documents'));
            });
        } catch (Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }
    }

    public function unpublish(User $actor, string $id): void
    {
        $this->authorise($actor, edit: true);
        DB::transaction(function () use ($actor, $id) {
            $school = School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $this->authorise($actor, edit: true);
            $documents = $this->documents();
            abort_unless(collect($documents)->contains('id', $id), 404);
            Settings::query()->updateOrCreate(['type' => 'school_documents'], ['message' => json_encode(array_values(array_filter($documents, fn ($document) => $document['id'] !== $id)), JSON_THROW_ON_ERROR)]);
            Audit::record('school.document_unpublished', $school, [], ['document_id' => $id], schoolId: $school->id);
            Outbox::record(new ContextChanged($school->id, 'school.documents'));
        });
        // Unpublishing removes the listing; shared public URLs remain valid.
    }
}
