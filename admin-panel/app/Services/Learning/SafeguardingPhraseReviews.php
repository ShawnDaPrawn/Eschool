<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The review state of each language's safeguarding phrase list (Action Plan
 * 3.2). A list ships as draft. HQ staff holding safeguarding.phrases record a
 * professional review (who, when, a note); the language is reviewed only
 * while the newest review says so for the list exactly as it is now, so any
 * change to the phrases makes it draft again.
 *
 * Draft never weakens detection: SafeguardingPhrases checks every list. What
 * draft blocks is offering the language in the tutor (TutorLanguages).
 * engineering/specs/ai/safeguarding-phrases.md.
 */
class SafeguardingPhraseReviews
{
    public const ABILITY = 'safeguarding.phrases';

    public const OUTCOMES = ['reviewed', 'changes_needed'];

    public function __construct(private readonly SafeguardingPhrases $phrases) {}

    /** Whether this language's list, as it is now, has a professional review. */
    public function reviewed(string $language): bool
    {
        return in_array($language, $this->reviewedLanguages(), true);
    }

    /** @return list<string> */
    public function reviewedLanguages(): array
    {
        $reviewed = [];
        foreach ($this->latest() as $language => $row) {
            if ($row->outcome === 'reviewed' && hash_equals($this->phrases->sha256($language), (string) $row->list_sha256)) {
                $reviewed[] = $language;
            }
        }

        return $reviewed;
    }

    /**
     * Each language's state for HQ, with its read-only phrases and history.
     *
     * @return list<array<string, mixed>>
     */
    public function overview(): array
    {
        $latest = $this->latest();
        $history = DB::table('safeguarding_phrase_reviews')->leftJoin('users', 'users.id', '=', 'safeguarding_phrase_reviews.recorded_by')
            ->orderByDesc('safeguarding_phrase_reviews.id')->limit(200)
            ->get(['safeguarding_phrase_reviews.*', 'users.first_name', 'users.last_name'])->groupBy('language');
        $rows = [];
        foreach (array_keys(SafeguardingPhrases::LANGUAGES) as $language) {
            $list = $this->phrases->list($language);
            $row = $latest[$language] ?? null;
            $current = $row !== null && hash_equals($list['sha256'], (string) $row->list_sha256);
            $rows[] = [
                'language' => $language, 'name' => $list['name'], 'sha256' => $list['sha256'],
                'state' => $current && $row->outcome === 'reviewed' ? 'reviewed' : 'draft',
                // English is the tutor's own language; review gates only the home languages.
                'gates_tutor' => array_key_exists($language, TutorLanguages::OFFERED),
                'offered_in_tutor' => array_key_exists($language, TutorLanguages::enabled()),
                'latest' => $row === null ? null : $this->present($row, $current),
                'history' => ($history[$language] ?? collect())->map(fn (object $review) => $this->present($review, hash_equals($list['sha256'], (string) $review->list_sha256)))->values()->all(),
                'categories' => $list['categories'],
                'phrase_count' => array_sum(array_map('count', $list['categories'])),
            ];
        }

        return $rows;
    }

    /**
     * Record one professional review of a language's list as it is now.
     *
     * @param  array<string, mixed>  $input
     */
    public function record(User $actor, string $language, array $input): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, self::ABILITY), 403);
        abort_unless(array_key_exists($language, SafeguardingPhrases::LANGUAGES), 404);
        $data = Validator::make($input, self::rules())->validate();
        $sha256 = $this->phrases->sha256($language);
        DB::transaction(function () use ($actor, $language, $data, $sha256) {
            abort_unless(app(PlatformStaffAccess::class)->can($actor, self::ABILITY), 403);
            // One writer at a time per language, reading the state it replaces under the lock.
            $before = DB::table('safeguarding_phrase_reviews')->where('language', $language)->orderByDesc('id')->lockForUpdate()->first();
            $wasReviewed = $before !== null && $before->outcome === 'reviewed' && hash_equals($sha256, (string) $before->list_sha256);
            DB::table('safeguarding_phrase_reviews')->insert(['language' => $language, 'outcome' => $data['outcome'], 'list_sha256' => $sha256,
                'reviewer' => trim((string) $data['reviewer']), 'reviewed_on' => $data['reviewed_on'], 'note' => trim((string) $data['note']),
                'recorded_by' => $actor->id, 'created_at' => now()]);
            // The language, outcome, reviewer, date, note and list hash: never a phrase.
            Audit::record('platform_safeguarding_phrase_review_recorded', null, ['language' => $language, 'state' => $wasReviewed ? 'reviewed' : 'draft'],
                ['language' => $language, 'state' => $data['outcome'] === 'reviewed' ? 'reviewed' : 'draft', 'outcome' => $data['outcome'],
                    'reviewer' => trim((string) $data['reviewer']), 'reviewed_on' => $data['reviewed_on'], 'note' => trim((string) $data['note']),
                    'list_sha256' => $sha256, 'changed_by' => $actor->id]);
        });
    }

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'outcome' => ['required', 'string', Rule::in(self::OUTCOMES)],
            'reviewer' => ['required', 'string', 'min:3', 'max:160'],
            'reviewed_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2026-01-01', 'before_or_equal:today'],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /** @return array<string, object> the newest review row per language */
    private function latest(): array
    {
        $ids = DB::table('safeguarding_phrase_reviews')->whereIn('language', array_keys(SafeguardingPhrases::LANGUAGES))
            ->groupBy('language')->selectRaw('MAX(id) as id')->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('safeguarding_phrase_reviews')->whereIn('id', $ids)->get()->keyBy('language')->all();
    }

    /** @return array{outcome: string, reviewer: string, reviewed_on: string, note: string, recorded_by: string, recorded_at: string, current: bool} */
    private function present(object $row, bool $current): array
    {
        return ['outcome' => (string) $row->outcome, 'reviewer' => (string) $row->reviewer, 'reviewed_on' => substr((string) $row->reviewed_on, 0, 10),
            'note' => (string) $row->note, 'recorded_by' => trim((string) ($row->first_name ?? '').' '.(string) ($row->last_name ?? '')),
            'recorded_at' => substr((string) $row->created_at, 0, 16), 'current' => $current];
    }
}
