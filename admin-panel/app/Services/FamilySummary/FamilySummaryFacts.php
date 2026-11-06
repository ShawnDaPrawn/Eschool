<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Communication\Message;
use App\Models\Communication\MessageRead;
use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\FamilyForms\ConsentResponse;
use App\Models\Notification;
use App\Models\Students;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\TimetableQueries;
use App\Services\Communication\RecipientAccess;
use App\Services\FamilyForms\FamilyFormAccess;
use App\Services\Learning\Diary\DiarySchoolItems;
use App\Services\Platform\Notifications\NotificationTypes;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The structured facts one school's part of a guardian's daily family
 * summary is built from (Action Plan 5.11), read in the CURRENT school
 * through each source's own family authority: today's and tomorrow's
 * timetable changes, homework due, forms waiting for the guardian, and how
 * many school messages and private updates are waiting.
 *
 * First names only. Marks, discipline, wellbeing, health and fees detail are
 * never read into a fact: private updates are a count, nothing more.
 */
class FamilySummaryFacts
{
    public const TYPE = 'family.daily_summary';

    /** @return array{school: string, date: string, children: list<array>, unread_messages: int, private_updates: int}|null null when the guardian has no current child here */
    public function collect(User $guardian, CarbonImmutable $now): ?array
    {
        $authority = app(FamilySummaryAuthority::class);
        $fresh = $authority->guardian($guardian);
        $school = app(CurrentSchool::class)->school();
        $today = $now->setTimezone($school?->timezone ?: 'Africa/Johannesburg')->startOfDay();
        $children = [];
        foreach ($authority->children($fresh) as $learner) {
            $learner = $authority->child($fresh, (int) $learner->id);
            $children[] = [
                'first_name' => $this->firstName($learner),
                'timetable' => $this->timetable($fresh, $learner, $today),
                'homework' => $this->homework($learner, $today),
                'forms' => $this->forms($fresh, $learner, $now),
            ];
        }
        if ($children === []) {
            return null;
        }
        $facts = [
            'school' => Str::limit((string) ($school?->short_name ?: $school?->name), 100, ''),
            'date' => $today->toDateString(),
            'children' => $children,
            'unread_messages' => $this->unreadMessages($fresh, $now),
            'private_updates' => $this->privateUpdates($fresh, $now),
        ];
        // The relationship is checked again after the reads, as the digests do.
        $authority->guardian($fresh);

        return $facts;
    }

    /** Whether a part has anything to say. */
    public static function hasContent(array $facts): bool
    {
        return self::hasChildItems($facts) || ($facts['unread_messages'] ?? 0) > 0 || ($facts['private_updates'] ?? 0) > 0;
    }

    public static function hasChildItems(array $facts): bool
    {
        foreach ($facts['children'] ?? [] as $child) {
            if (($child['timetable'] ?? []) !== [] || ($child['homework'] ?? []) !== [] || ($child['forms'] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /** Whether a notice type is a private update the summary only counts. */
    public static function isPrivateUpdate(string $type): bool
    {
        if ($type === self::TYPE) {
            return false;
        }
        if (NotificationTypes::isPrivate($type)) {
            return true;
        }
        foreach ((array) config('family_summary.private_types', []) as $prefix) {
            if (is_string($prefix) && $prefix !== '' && ($type === $prefix || (str_ends_with($prefix, '.') && str_starts_with($type, $prefix)))) {
                return true;
            }
        }

        return false;
    }

    private function firstName(Students $learner): string
    {
        $name = trim((string) User::query()->whereKey($learner->user_id)->value('first_name'));

        // A first name only; a double-barrelled one keeps its first word.
        return Str::limit(Str::before($name, ' ') ?: __('Your child'), 40, '');
    }

    private function max(): int
    {
        return max(1, min(20, (int) config('family_summary.max_items', 5)));
    }

    /** @return list<array{day: string, kind: string, subject?: ?string, period?: ?string, pattern?: ?string}> */
    private function timetable(User $fresh, Students $learner, CarbonImmutable $today): array
    {
        $days = [$today->toDateString() => 'today', $today->addDay()->toDateString() => 'tomorrow'];
        try {
            $week = app(TimetableQueries::class)->week($fresh, (int) schoolSetting('session_year'), $today->toDateString(), (int) $learner->id);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
        $changes = [];
        foreach ($week['exam_days'] ?? [] as $date) {
            if (isset($days[$date])) {
                $changes[$days[$date].':exam'] = ['day' => $days[$date], 'kind' => 'exam_day'];
            }
        }
        foreach ($week['lessons'] ?? [] as $lesson) {
            $day = $days[$lesson['date'] ?? ''] ?? null;
            if ($day === null) {
                continue;
            }
            if (isset($lesson['day_pattern'])) {
                $changes[$day.':pattern'] = ['day' => $day, 'kind' => 'short_day', 'pattern' => Str::limit((string) $lesson['day_pattern'], 60, '')];
            }
            if (isset($lesson['cover_id'])) {
                // The subject and period only: never a teacher's name or why they are away.
                $changes[$day.':cover:'.$lesson['cover_id']] = ['day' => $day, 'kind' => 'cover',
                    'subject' => Str::limit((string) ($lesson['subject_name'] ?? $lesson['title'] ?? ''), 60, ''), 'period' => Str::limit((string) ($lesson['period_name'] ?? ''), 40, '')];
            }
        }

        return array_slice(array_values($changes), 0, $this->max() * 2);
    }

    /** @return list<array{day: string, subject: ?string, title: string, time: string}> */
    private function homework(Students $learner, CarbonImmutable $today): array
    {
        $out = [];
        foreach (app(DiarySchoolItems::class)->dueBetween($learner, $today, $today->addDay()->endOfDay(), $today) as $item) {
            $out[] = ['day' => substr($item['due_at'], 0, 10) === $today->toDateString() ? 'today' : 'tomorrow',
                'subject' => $item['subject'] === null ? null : Str::limit($item['subject'], 60, ''), 'title' => Str::limit($item['title'], 80, ''), 'time' => substr($item['due_at'], 11, 5)];
        }

        return array_slice($out, 0, $this->max());
    }

    /** Open consent forms for this child that no one has answered for its current wording. */
    private function forms(User $fresh, Students $learner, CarbonImmutable $now): array
    {
        try {
            app(FamilyFormAccess::class)->guardian($fresh, (int) $learner->id);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
        $zone = app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg';
        $open = ConsentRequest::query()->where('status', 'open')->where('respond_by', '>', $now->utc()->format('Y-m-d H:i:s'))->select('id');
        $out = [];
        foreach (ConsentRecipient::query()->where('learner_id', $learner->id)->whereIn('request_id', $open)->orderBy('created_at')->limit(50)->get() as $recipient) {
            if (ConsentResponse::query()->where('recipient_id', $recipient->id)->where('revision', $recipient->revision)->exists()) {
                continue;
            }
            $request = ConsentRequest::query()->findOrFail($recipient->request_id);
            $out[] = ['title' => Str::limit((string) $request->title, 80, ''), 'reply_by' => CarbonImmutable::parse($request->respond_by)->setTimezone($zone)->toDateString()];
            if (count($out) >= $this->max()) {
                break;
            }
        }

        return $out;
    }

    /** School messages to this guardian that they have not opened. A count only. */
    private function unreadMessages(User $fresh, CarbonImmutable $now): int
    {
        try {
            $ids = app(RecipientAccess::class)->messageIds($fresh);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return 0;
        }
        if ($ids === []) {
            return 0;
        }
        $since = $now->setTimezone((string) config('app.timezone', 'UTC'))->subDays(max(1, (int) config('family_summary.unread_message_days', 7)))->format('Y-m-d H:i:s');
        $candidates = Message::query()->whereIntegerInRaw('id', $ids)->whereIn('state', ['dispatching', 'sent'])->whereNotNull('published_at')
            ->where('published_at', '>=', $since)->whereNotIn('id', MessageRead::query()->where('user_id', $fresh->id)->select('message_id'))
            ->orderByDesc('id')->limit(100)->pluck('id');
        $count = 0;
        foreach ($candidates as $id) {
            try {
                app(MessagePolicy::class)->reader($fresh, (int) $id);
                $count++;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $count;
    }

    /**
     * Private updates delivered here in the last day (marks, discipline,
     * wellbeing, fees and every private type), through the same visibility
     * the inbox uses, so a withdrawn source is not counted. A count only.
     */
    private function privateUpdates(User $fresh, CarbonImmutable $now): int
    {
        $local = $now->setTimezone((string) config('app.timezone', 'UTC'));
        $types = NotificationVisibility::forUser(Notification::query()->where('school_id', app(CurrentSchool::class)->id()), (int) $fresh->id, 0)
            ->where('date', '>', $local->subDay()->format('Y-m-d H:i:s'))->where('date', '<=', $local->format('Y-m-d H:i:s'))
            ->where('type', '!=', self::TYPE)->limit(500)->pluck('type');

        return $types->filter(fn ($type) => self::isPrivateUpdate((string) $type))->count();
    }
}
