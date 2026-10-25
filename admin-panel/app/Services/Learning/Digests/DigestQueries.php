<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\DigestLanguageTemplate;
use App\Models\Learning\DigestPreference;
use App\Models\Learning\WeeklyParentDigest;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DigestQueries
{
    public function children(User $actor): array
    {
        $items = [];
        $rows = app(DigestAuthority::class)->candidates($actor)->orderBy('id')->limit(1001)->get(['id', 'user_id']);
        abort_if($rows->count() > 1000, 422);
        foreach ($rows as $learner) {
            try {
                app(DigestAuthority::class)->family($actor, (int) $learner->id);
                $items[] = ['id' => (int) $learner->id, 'name' => User::acrossSchools()->findOrFail($learner->user_id)->full_name];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $items;
    }

    public function access(User $actor): array
    {
        app(DigestAuthority::class)->actor($actor);
        $parent = true;
        try {
            app(DigestAuthority::class)->parent($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $parent = false;
        }
        $manager = true;
        try {
            app(DigestAuthority::class)->manager($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $manager = false;
        }
        $preference = $parent ? DigestPreference::where('user_id', $actor->id)->first() : null;

        return ['school_id' => app(CurrentSchool::class)->id(), 'account_id' => (int) $actor->id,
            'parent' => $parent, 'can_manage' => $manager, 'children' => $parent ? $this->children($actor) : [],
            'language' => $preference?->language ?? 'en', 'preference_revision' => (int) ($preference?->revision ?? 0),
            'languages' => config('weekly_digests.languages'), 'approved_languages' => DigestLanguageTemplate::whereNotNull('approved_at')->whereNull('revoked_at')->distinct()->pluck('language')->all()];
    }

    public function preferences(User $actor, array $data): array
    {
        $authorize = fn () => app(DigestAuthority::class)->parent($actor, true);
        $authorize();
        Validator::make($data, ['language' => ['required', Rule::in(config('weekly_digests.languages'))], 'revision' => ['required', 'integer', 'min:0']])->validate();

        return app(DigestOperations::class)->run($actor, 'preference', $data, $authorize, function () use ($actor, $data): array {
            $old = DigestPreference::where('user_id', $actor->id)->lockForUpdate()->first();
            abort_unless((int) ($old?->revision ?? 0) === (int) $data['revision'], 409, 'Your preferred digest language changed. Refresh before saving.');
            $old ??= new DigestPreference(['user_id' => $actor->id]);
            $old->language = $data['language'];
            $old->revision = (int) ($old->revision ?? 0) + 1;
            $old->save();

            return ['id' => (int) $old->id, 'revision' => (int) $old->revision];
        });
    }

    /** Pins provenance and refreshes actual family authority for each SQL execution. */
    public function owned(User $actor): Builder
    {
        app(DigestAuthority::class)->parent($actor);
        $schoolId = app(CurrentSchool::class)->id();
        $actorId = (int) $actor->id;

        return WeeklyParentDigest::where('school_id', $schoolId)->where('recipient_id', $actorId)
            ->withGlobalScope('digest_fresh_family', function (Builder $query) use ($actorId, $schoolId): void {
                $school = app(CurrentSchool::class);
                if (! $school->isSet() || $school->id() !== $schoolId || $school->isAcrossSchools() || $school->isImpersonating()) {
                    $query->whereRaw('1 = 0');

                    return;
                }
                try {
                    $fresh = app(DigestAuthority::class)->parent(User::findOrFail($actorId));
                    $ids = [];
                    foreach (app(DigestAuthority::class)->candidates($fresh)->limit(1001)->get(['id', 'user_id']) as $learner) {
                        try {
                            app(DigestAuthority::class)->family($fresh, (int) $learner->id);
                            $ids[] = (int) $learner->id;
                        } catch (HttpExceptionInterface|ModelNotFoundException) {
                        }
                    }
                    abort_if(count($ids) > 1000, 422);
                    $query->whereIn('weekly_parent_digests.learner_id', $ids)->where('weekly_parent_digests.expires_at', '>', now())
                        ->whereExists(fn ($templates) => $templates->selectRaw('1')->from('digest_language_templates')
                            ->whereColumn('digest_language_templates.id', 'weekly_parent_digests.template_id')
                            ->whereColumn('digest_language_templates.school_id', 'weekly_parent_digests.school_id')
                            ->whereNotNull('digest_language_templates.approved_at')->whereNull('digest_language_templates.revoked_at'));
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    $query->whereRaw('1 = 0');
                }
            });
    }

    public function list(User $actor, int $learnerId, ?int $before = null): array
    {
        app(DigestAuthority::class)->family($actor, $learnerId);
        $query = $this->owned($actor)->where('learner_id', $learnerId);
        $total = (clone $query)->count();
        $rows = $query->when($before !== null, fn ($q) => $q->where('id', '<', $before))->latest('id')->limit(13)->get();
        $items = $rows->take(12)->map(fn ($digest) => ['id' => (int) $digest->id, 'learner_id' => (int) $digest->learner_id,
            'language' => $digest->language, 'week_start' => $digest->week_start->toDateString(), 'week_end' => $digest->week_end->toDateString(),
            'title' => $digest->title, 'opened' => $digest->opened_at !== null])->all();
        app(DigestAuthority::class)->family($actor, $learnerId);

        return ['items' => $items, 'total' => $total, 'next_before' => $rows->count() > 12 ? (int) $rows->take(12)->last()->id : null];
    }

    public function show(User $actor, int $id): array
    {
        $metadata = $this->owned($actor)->select(['id', 'learner_id'])->findOrFail($id);
        app(DigestAuthority::class)->family($actor, (int) $metadata->learner_id);
        $digest = $this->owned($actor)->findOrFail($id);
        $digest->opened_at ??= now();
        $digest->save();
        $data = ['id' => (int) $digest->id, 'learner_id' => (int) $digest->learner_id, 'language' => $digest->language,
            'week_start' => $digest->week_start->toDateString(), 'week_end' => $digest->week_end->toDateString(),
            'template_version' => (int) $digest->template_version, 'title' => $digest->title, 'summary' => $digest->summary,
            'evidence' => $digest->snapshot, 'home_action' => $digest->home_action, 'expires_at' => $digest->expires_at->toISOString()];
        app(DigestAuthority::class)->family($actor, (int) $digest->learner_id);

        return $data;
    }

    public function templates(User $actor): array
    {
        app(DigestAuthority::class)->manager($actor);
        $rows = [];
        foreach (config('weekly_digests.languages') as $language) {
            $latest = DigestLanguageTemplate::where('language', $language)->latest('version')->first();
            $approved = app(DigestTemplates::class)->approved($language);
            foreach (collect([$latest, $approved])->filter()->unique('id') as $template) {
                $rows[] = ['id' => (int) $template->id, 'language' => $template->language, 'version' => (int) $template->version,
                    'revision' => (int) $template->revision, 'title' => $template->title_template, 'body' => $template->body_template,
                    'actions' => $template->action_templates, 'notice_prompt' => $template->notice_prompt,
                    'approved' => $template->approved_at !== null && $template->revoked_at === null];
            }
        }
        app(DigestAuthority::class)->manager($actor);

        return ['items' => $rows, 'starting_templates' => config('weekly_digests.starting_templates')];
    }
}
