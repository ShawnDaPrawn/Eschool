<?php

declare(strict_types=1);

namespace App\Services\EarlyYears;

use App\Models\Academics\AcademicLeadership;
use App\Models\EarlyYears\DevelopmentArea;
use App\Models\EarlyYears\DiaryEntry;
use App\Models\EarlyYears\EarlyConcern;
use App\Models\EarlyYears\Observation;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Gallery\GalleryAuthority;
use App\Services\Gallery\GalleryEvents;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryQueries;
use App\Services\Gallery\GallerySettings;
use App\Services\Gallery\GalleryTags;
use App\Services\Growth\Interventions;
use App\Services\Growth\RiskAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Early years observations and the portfolio they build (Action Plan 4.3).
 *
 * A photograph goes through the private gallery: scanned, reduced to clean
 * pixels, encrypted, and stored as the learner's family photo with a proposed
 * tag. The family decides that tag under the school's photo consent rule,
 * exactly as for any gallery tag. Staff see the photograph in the portfolio
 * only once the tag is approved; a learner nobody can give consent for gets
 * no photograph at all.
 *
 * An early concern is referred into the existing interventions flow: an
 * intervention owned by a current grade head of the learner's grade. It is
 * a description of what was seen, never a diagnosis.
 */
class Observations
{
    public const NOTE_MAX = 2000;

    public const CONCERN_MAX = 2000;

    public function __construct(private EarlyYearsAuthority $authority) {}

    /**
     * Could anyone give consent for a photograph of this learner under the
     * school's current rule? The same decision the gallery uses to approve a
     * tag: a current verified guardian with approval rights, or the learner
     * themself where the school allows self-approval.
     */
    public function consentAvailable(int $learnerId): bool
    {
        $learner = Students::findOrFail($learnerId);
        $candidates = GuardianLink::where('learner_id', $learnerId)->where('status', 'active')->where('can_approve', true)
            ->orderBy('id')->limit(20)->pluck('guardian_user_id')->map(fn ($id) => (int) $id)->all();
        if (! app(GallerySettings::class)->current()->guardian_required && $learner->user_id !== null) {
            $candidates[] = (int) $learner->user_id;
        }
        foreach (array_unique($candidates) as $userId) {
            $person = User::query()->find($userId);
            if ($person === null) {
                continue;
            }
            try {
                app(GalleryTags::class)->decisionAuthority($person, $learnerId);

                return true;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return false;
    }

    /** Whether the gallery is on for this person; photographs need it. */
    private function galleryOn(User $actor, bool $write = false): bool
    {
        try {
            app(GalleryAuthority::class)->actor($actor, $write);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** approved, awaiting (the family has not decided) or declined (refused, removed or withdrawn). */
    private function photoState(Observation $observation): string
    {
        $photo = GalleryPhoto::select(['id', 'active'])->find($observation->gallery_photo_id);
        $tag = PhotoTag::where('gallery_photo_id', $observation->gallery_photo_id)->where('learner_id', $observation->learner_id)->first();
        if ($photo === null || ! $photo->active || $tag === null || ! in_array($tag->status, ['proposed', 'approved'], true)) {
            return 'declined';
        }

        return app(GalleryAuthority::class)->approved($tag) ? 'approved' : 'awaiting';
    }

    /** A class page: its children today, their observation counts and how many diary entries today has. */
    public function classroom(User $actor, int $sectionId): array
    {
        $section = $this->authority->classRead($actor, $sectionId);
        $learners = $this->authority->roster($sectionId);
        $counts = Observation::whereIn('learner_id', $learners->pluck('id'))->whereNull('withdrawn_at')->selectRaw('learner_id, count(*) as total')->groupBy('learner_id')->pluck('total', 'learner_id');
        $today = $this->authority->today();
        $diary = DiaryEntry::whereIn('learner_id', $learners->pluck('id'))->whereDate('entry_date', $today)->count();
        $this->authority->classRead($actor, $sectionId);

        return ['section' => $section, 'learners' => $learners, 'counts' => $counts, 'today' => $today, 'diaryCount' => $diary];
    }

    public function portfolio(User $actor, int $learnerId): array
    {
        $section = $this->authority->learnerRead($actor, $learnerId);
        $learner = Students::with('user')->findOrFail($learnerId);
        $areas = app(DevelopmentAreas::class)->all();
        $gallery = $this->galleryOn($actor);
        $rows = Observation::where('learner_id', $learnerId)->whereNull('withdrawn_at')->orderByDesc('observed_on')->orderByDesc('id')->limit(301)->get();
        $concerns = EarlyConcern::whereIn('observation_id', $rows->pluck('id'))->get()->keyBy('observation_id');
        $names = User::query()->whereIn('id', $rows->pluck('recorded_by')->merge($concerns->pluck('raised_by'))->unique())->get()->mapWithKeys(fn (User $user) => [(int) $user->id => (string) $user->full_name]);
        $items = $rows->take(300)->map(function (Observation $row) use ($gallery, $concerns, $names, $actor): array {
            $concern = $concerns->get($row->id);

            return ['id' => (int) $row->id, 'development_area_id' => (int) $row->development_area_id, 'observed_on' => $row->observed_on->toDateString(),
                'note' => $row->note, 'recorded_by' => $names[(int) $row->recorded_by] ?? '', 'mine' => (int) $row->recorded_by === (int) $actor->id, 'revision' => (int) $row->revision,
                'photo' => $row->gallery_photo_id === null ? null : ($gallery ? $this->photoState($row) : 'unavailable'),
                'concern' => $concern === null ? null : ['raised_on' => $concern->created_at?->toDateString(), 'raised_by' => $names[(int) $concern->raised_by] ?? '']];
        })->all();
        $write = true;
        try {
            $this->authority->learnerWrite($actor, $learnerId);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $write = false;
        }
        $this->authority->learnerRead($actor, $learnerId);

        return ['learner' => ['id' => (int) $learner->id, 'name' => (string) $learner->user?->full_name], 'section' => $section, 'areas' => $areas,
            'items' => $items, 'more' => $rows->count() > 300, 'can_write' => $write, 'photos' => $write && $this->galleryOn($actor, true),
            'consent_available' => $write && $this->galleryOn($actor, true) && $this->consentAvailable($learnerId),
            'concern_owners' => $write ? $this->concernOwners($learnerId) : []];
    }

    public function record(User $actor, int $learnerId, array $input, ?UploadedFile $file = null): Observation
    {
        $this->authority->learnerWrite($actor, $learnerId);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'],
            'development_area_id' => ['required', 'integer', 'min:1'],
            'observed_on' => ['required', 'date_format:Y-m-d'],
            'note' => [$file === null ? 'required' : 'nullable', 'string', 'max:'.self::NOTE_MAX],
        ], ['note.required' => __('Write a note or add a photograph.')])->validate();
        $year = $this->authority->year();
        abort_unless($data['observed_on'] <= $this->authority->today() && $data['observed_on'] >= substr((string) $year->start_date, 0, 10), 422, __('Choose a day in the current school year, up to today.'));
        DevelopmentArea::where('active', true)->findOrFail((int) $data['development_area_id']);
        $note = trim((string) ($data['note'] ?? ''));
        $hash = hash_hmac('sha256', json_encode([$learnerId, (int) $data['development_area_id'], $data['observed_on'], $note, $file === null ? null : hash_file('sha256', (string) $file->getRealPath())], JSON_THROW_ON_ERROR), (string) config('app.key'));
        $prior = Observation::where('recorded_by', $actor->id)->where('operation_id', $data['operation_id'])->first();
        if ($prior !== null) {
            abort_unless(hash_equals($prior->request_hash, $hash) && (int) $prior->learner_id === $learnerId, 409, __('This observation was already saved with different details.'));

            return $prior;
        }
        $photo = function () use ($actor, $learnerId): void {
            abort_unless($this->galleryOn($actor, true), 422, __('Photographs need the private photo gallery. Ask the school to switch it on, or record a note.'));
            abort_unless(app(GalleryQueries::class)->currentLearner(Students::findOrFail($learnerId)), 404);
            abort_unless($this->consentAvailable($learnerId), 422, __('Nobody can give photo consent for this learner yet, so no photograph can be kept. Record a note instead.'));
        };
        $prepared = null;
        if ($file !== null) {
            $photo();
            Validator::make(['photo' => $file], SafeImages::rules())->validate();
            $prepared = app(GalleryPhotos::class)->prepare($file);
        }
        $paths = [];
        try {
            return DB::transaction(function () use ($actor, $learnerId, $data, $note, $hash, $prepared, $photo, $year, &$paths): Observation {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $section = $this->authority->learnerWrite($actor, $learnerId);
                $prior = Observation::where('recorded_by', $actor->id)->where('operation_id', $data['operation_id'])->first();
                if ($prior !== null) {
                    abort_unless(hash_equals($prior->request_hash, $hash), 409, __('This observation was already saved with different details.'));

                    return $prior;
                }
                $photoId = null;
                if ($prepared !== null) {
                    $photo();
                    $photoId = (int) app(GalleryPhotos::class)->storeForLearner($actor, $year, $learnerId, $data['observed_on'], $prepared, $paths)->id;
                }
                $row = Observation::create(['learner_id' => $learnerId, 'class_section_id' => $section->id, 'session_year_id' => $year->id, 'development_area_id' => (int) $data['development_area_id'],
                    'observed_on' => $data['observed_on'], 'note' => $note === '' ? null : $note, 'gallery_photo_id' => $photoId, 'recorded_by' => $actor->id,
                    'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'revision' => 1]);
                Audit::record('early_years.observation_recorded', $row, [], ['learner_id' => $learnerId, 'development_area_id' => $row->development_area_id, 'photo' => $photoId !== null]);
                $this->authority->learnerWrite($actor, $learnerId);

                return $row;
            });
        } catch (Throwable $error) {
            foreach ($paths as $path) {
                Storage::disk(GalleryPhotos::DISK)->delete($path);
            }
            throw $error;
        }
    }

    /** Consented pixels only: the tag must be approved under the school's current rule. */
    public function photo(User $actor, int $observationId, bool $thumbnail = false): string
    {
        $row = Observation::whereNull('withdrawn_at')->whereNotNull('gallery_photo_id')->findOrFail($observationId);
        $this->authority->learnerRead($actor, (int) $row->learner_id);
        app(GalleryAuthority::class)->actor($actor);
        abort_unless($this->photoState($row) === 'approved', 404);
        $photo = GalleryPhoto::where('active', true)->findOrFail($row->gallery_photo_id);
        $bytes = app(GalleryPhotos::class)->read($photo, $thumbnail);
        $this->authority->learnerRead($actor, (int) $row->learner_id);
        abort_unless($this->photoState($row) === 'approved' && (int) GalleryPhoto::whereKey($photo->id)->value('revision') === (int) $photo->revision, 409, __('This photograph changed while opening it.'));

        return $bytes;
    }

    /** Only the teacher who recorded an observation withdraws it; its photograph leaves the gallery too. */
    public function withdraw(User $actor, int $observationId, array $input): Observation
    {
        $row = Observation::whereNull('withdrawn_at')->findOrFail($observationId);
        $this->authority->learnerWrite($actor, (int) $row->learner_id);
        abort_unless((int) $row->recorded_by === (int) $actor->id, 403, __('Only the teacher who recorded this observation can withdraw it.'));
        $data = Validator::make($input, ['revision' => ['required', 'integer', 'min:1']])->validate();

        return DB::transaction(function () use ($actor, $observationId, $data): Observation {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $row = Observation::lockForUpdate()->findOrFail($observationId);
            $this->authority->learnerWrite($actor, (int) $row->learner_id);
            abort_unless((int) $row->recorded_by === (int) $actor->id, 403);
            abort_unless($row->withdrawn_at === null && (int) $row->revision === (int) $data['revision'], 409, __('This observation changed. Reload before withdrawing it.'));
            abort_if(EarlyConcern::where('observation_id', $row->id)->exists(), 422, __('This observation was referred as an early concern, so it stays in the portfolio.'));
            $row->update(['withdrawn_at' => now(), 'withdrawn_by' => $actor->id, 'revision' => $row->revision + 1]);
            if ($row->gallery_photo_id !== null) {
                $photo = GalleryPhoto::lockForUpdate()->find($row->gallery_photo_id);
                if ($photo !== null && $photo->active) {
                    $photo->update(['active' => false, 'revision' => $photo->revision + 1]);
                    app(GalleryEvents::class)->record('photo_withdrawn', $actor, $photo);
                }
            }
            Audit::record('early_years.observation_withdrawn', $row, [], ['revision' => $row->revision]);

            return $row;
        });
    }

    /** @return list<array{id: int, name: string}> current grade heads who can take a referral for this learner */
    public function concernOwners(int $learnerId): array
    {
        $risk = app(RiskAuthority::class);
        $year = (int) $this->authority->year()->id;
        try {
            $risk->enabled(true);
            $grade = $risk->currentGrade($learnerId, $year);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
        $today = $this->authority->today();
        $owners = [];
        $ids = AcademicLeadership::where('kind', 'grade_head')->where('grade_level_id', $grade)->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('id')->limit(20)->pluck('user_id')->unique();
        foreach ($ids as $id) {
            try {
                $person = $risk->caseActor(User::query()->findOrFail($id), $learnerId, $year, $grade, true, 'leadership');
                $owners[] = ['id' => (int) $person->id, 'name' => (string) $person->full_name];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $owners;
    }

    /**
     * Refer an early concern from an observation to a grade head as an
     * intervention. Plain words about what was seen; there is no diagnosis
     * field, and the text is kept only in the encrypted intervention.
     */
    public function raiseConcern(User $actor, int $observationId, array $input): EarlyConcern
    {
        $row = Observation::whereNull('withdrawn_at')->findOrFail($observationId);
        $learner = (int) $row->learner_id;
        $this->authority->learnerWrite($actor, $learner);
        $year = $this->authority->year();
        $data = Validator::make($input, [
            'concern' => ['required', 'string', 'min:10', 'max:'.self::CONCERN_MAX],
            'owner_id' => ['required', 'integer', 'min:1'],
            'review_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->authority->today(), 'before_or_equal:'.substr((string) $year->end_date, 0, 10)],
        ])->validate();
        abort_unless(in_array((int) $data['owner_id'], array_column($this->concernOwners($learner), 'id'), true), 422, __('Choose a grade head for this learner\'s grade. Early warning must be switched on and a grade head appointed.'));
        abort_if(EarlyConcern::where('observation_id', $row->id)->exists(), 409, __('This observation has already been referred.'));
        $area = DevelopmentArea::whereKey($row->development_area_id)->value('name');
        $action = __('Early concern from an early years observation. This is what the teacher saw, not a diagnosis.')."\n"
            .__('Development area: :area', ['area' => $area])."\n".__('Observed on: :date', ['date' => $row->observed_on->toDateString()])."\n\n".trim($data['concern']);

        return DB::transaction(function () use ($actor, $observationId, $learner, $year, $data, $action): EarlyConcern {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $row = Observation::whereNull('withdrawn_at')->lockForUpdate()->findOrFail($observationId);
            abort_if(EarlyConcern::where('observation_id', $row->id)->exists(), 409, __('This observation has already been referred.'));
            $intervention = app(Interventions::class)->refer($actor, $learner, (int) $year->id, (int) $data['owner_id'], $action, $data['review_on'],
                fn () => $this->authority->learnerWrite($actor, $learner));
            $concern = EarlyConcern::create(['observation_id' => $row->id, 'learner_id' => $learner, 'intervention_id' => $intervention['id'], 'raised_by' => $actor->id]);
            Audit::record('early_years.concern_referred', $concern, [], ['observation_id' => $row->id, 'intervention_id' => $intervention['id'], 'owner_id' => (int) $data['owner_id']]);

            return $concern;
        });
    }
}
