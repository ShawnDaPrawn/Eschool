<?php

declare(strict_types=1);

namespace App\Services\Learning\Diary;

use App\Models\Learning\DiaryEntry;
use App\Models\Learning\DiaryItemState;
use App\Models\Students;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A learner's own notes and to-dos, and their layer on school items. Every
 * query is bound to the owner's user id; another learner's row is "not found".
 */
class DiaryEntries
{
    public const MAX_ENTRIES = 2000;

    public const MAX_REMINDERS = 500;

    public function __construct(private readonly DiaryCipher $cipher, private readonly DiarySchoolItems $items) {}

    /**
     * The range the learner is looking at: dated entries in it, undated
     * to-dos that are open or were done during it (so a done one can still be
     * found, reopened or deleted on the day it was done), and the school items.
     */
    public function range(Students $learner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $today = $this->today();
        $userId = (int) $learner->user_id;
        $zone = $this->zone();
        $doneFrom = CarbonImmutable::parse($from->toDateString(), $zone)->startOfDay()->setTimezone(config('app.timezone'));
        $doneTo = CarbonImmutable::parse($to->toDateString(), $zone)->endOfDay()->setTimezone(config('app.timezone'));
        $entries = DiaryEntry::query()->where('user_id', $userId)
            ->where(fn ($q) => $q->whereBetween('entry_on', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q) => $q->whereNull('entry_on')->where('kind', 'todo')
                    ->where(fn ($q) => $q->whereNull('done_at')->orWhereBetween('done_at', [$doneFrom, $doneTo]))))
            ->orderByRaw('entry_on is null')->orderBy('entry_on')->orderBy('id')
            ->limit(self::MAX_ENTRIES)->get();

        return [
            'school_id' => (int) $learner->school_id,
            'reader_id' => $userId,
            'today' => $today->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'entries' => $entries->map(fn (DiaryEntry $entry) => $this->present($entry))->values()->all(),
            'school_items' => $this->items->between($learner, $from, $to, $today),
        ];
    }

    public function create(Students $learner, array $input): array
    {
        $userId = (int) $learner->user_id;

        return DB::transaction(function () use ($userId, $input) {
            // The owner's rows are locked, so a retried or concurrent create cannot pass the limit or duplicate.
            $existing = DiaryEntry::query()->where('user_id', $userId)->lockForUpdate()->get(['id', 'operation_id']);
            $original = $existing->firstWhere('operation_id', $input['operation_id']);
            if ($original) {
                return $this->present(DiaryEntry::query()->findOrFail($original->id));
            }
            if ($existing->count() >= self::MAX_ENTRIES) {
                throw new HttpException(422, 'The diary is full. Delete some entries first.');
            }

            $entry = new DiaryEntry;
            $entry->user_id = $userId;
            $entry->operation_id = $input['operation_id'];
            $entry->kind = $input['kind'];
            $entry->revision = 1;
            $this->fill($entry, $userId, $input);
            $entry->save();

            return $this->present($entry);
        });
    }

    public function update(Students $learner, int $id, array $input): array
    {
        $userId = (int) $learner->user_id;

        return DB::transaction(function () use ($userId, $id, $input) {
            $entry = DiaryEntry::query()->where('user_id', $userId)->lockForUpdate()->findOrFail($id);
            if ((int) $entry->revision !== (int) $input['revision']) {
                throw new HttpException(409, 'This entry changed on another device. Reload it and try again.');
            }
            $this->fill($entry, $userId, $input);
            $entry->revision = (int) $entry->revision + 1;
            $entry->save();

            return $this->present($entry);
        });
    }

    public function delete(Students $learner, int $id): void
    {
        DiaryEntry::query()->where('user_id', (int) $learner->user_id)->findOrFail($id)->delete();
    }

    /** The learner's own layer on a school item that applies to them now. */
    public function setItemState(Students $learner, string $source, int $id, array $input): array
    {
        abort_unless(in_array($source, DiarySchoolItems::SOURCES, true) && $this->items->applies($learner, $source, $id), 404);
        $userId = (int) $learner->user_id;
        $note = isset($input['note']) && trim((string) $input['note']) !== '' ? (string) $input['note'] : null;
        $done = (bool) ($input['done'] ?? false);
        $remind = $this->instant($input['remind_at'] ?? null);

        if (! $done && $note === null && $remind === null) {
            DiaryItemState::query()->where('user_id', $userId)->where('source', $source)->where('source_id', $id)->delete();
        } else {
            $state = DiaryItemState::query()->firstOrNew(['user_id' => $userId, 'source' => $source, 'source_id' => $id]);
            $state->done_at = $done ? ($state->done_at ?? now()) : null;
            $state->remind_at = $remind;
            $state->note = $this->cipher->encrypt($userId, $note);
            $state->save();
        }

        return ['source' => $source, 'id' => $id, 'done' => $done, 'remind_at' => $remind?->format('Y-m-d H:i'), 'note' => $note];
    }

    /**
     * Every reminder still to come, as instants: the learner's phone replaces
     * the diary reminders it has scheduled with exactly these. Done entries,
     * handed-in homework and items that no longer apply have none.
     */
    public function reminders(Students $learner): array
    {
        $userId = (int) $learner->user_id;
        $zone = $this->zone();
        $after = CarbonImmutable::now($zone)->format('Y-m-d H:i:s');
        $reminders = [];
        $entries = DiaryEntry::query()->where('user_id', $userId)->whereNull('done_at')
            ->whereNotNull('remind_at')->where('remind_at', '>', $after)
            ->orderBy('remind_at')->limit(self::MAX_REMINDERS)->get();
        foreach ($entries as $entry) {
            $reminders[] = ['key' => 'entry:'.$entry->id, 'at' => $this->utc($entry->remind_at, $zone),
                'text' => (string) $this->cipher->decrypt($userId, $entry->title)];
        }
        $states = DiaryItemState::query()->where('user_id', $userId)->whereNull('done_at')
            ->whereNotNull('remind_at')->where('remind_at', '>', $after)
            ->orderBy('remind_at')->limit(self::MAX_REMINDERS)->get();
        $titles = $this->items->openTitles($learner, $states);
        foreach ($states as $state) {
            $key = $state->source.':'.$state->source_id;
            if (isset($titles[$key])) {
                $reminders[] = ['key' => $key, 'at' => $this->utc($state->remind_at, $zone), 'text' => $titles[$key]];
            }
        }
        usort($reminders, fn (array $a, array $b) => [$a['at'], $a['key']] <=> [$b['at'], $b['key']]);

        return [
            'school_id' => (int) $learner->school_id,
            'reader_id' => $userId,
            'reminders' => array_slice($reminders, 0, self::MAX_REMINDERS),
        ];
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->zone())->startOfDay();
    }

    private function zone(): string
    {
        return schoolSetting('time_zone', 'UTC') ?: 'UTC';
    }

    /** A stored school wall time as a UTC instant. */
    private function utc(\DateTimeInterface $wall, string $zone): string
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $wall->format('Y-m-d H:i:s'), $zone)->utc()->format('Y-m-d\\TH:i:s\\Z');
    }

    private function fill(DiaryEntry $entry, int $userId, array $input): void
    {
        // Only open to-dos show without a date; an undated note would never be seen again.
        if ($entry->kind === 'note' && blank($input['date'] ?? null)) {
            throw ValidationException::withMessages(['date' => 'A note needs a date.']);
        }
        $entry->title = $this->cipher->encrypt($userId, trim((string) $input['title']));
        $body = isset($input['body']) && trim((string) $input['body']) !== '' ? (string) $input['body'] : null;
        $entry->body = $this->cipher->encrypt($userId, $body);
        $entry->entry_on = $input['date'] ?? null;
        $entry->remind_at = $this->instant($input['remind_at'] ?? null);
        if ($entry->kind === 'todo') {
            $done = (bool) ($input['done'] ?? false);
            $entry->done_at = $done ? ($entry->done_at ?? now()) : null;
        } else {
            $entry->done_at = null;
        }
    }

    /** Reminders are school-local wall times ("Y-m-d H:i"), stored as written, like assignment due dates. */
    private function instant(?string $value): ?CarbonImmutable
    {
        return $value === null || $value === '' ? null : CarbonImmutable::createFromFormat('!Y-m-d H:i', $value);
    }

    private function present(DiaryEntry $entry): array
    {
        $userId = (int) $entry->user_id;

        return [
            'id' => (int) $entry->id,
            'kind' => (string) $entry->kind,
            'title' => (string) $this->cipher->decrypt($userId, $entry->title),
            'body' => $this->cipher->decrypt($userId, $entry->body),
            'date' => $entry->entry_on?->toDateString(),
            'remind_at' => $entry->remind_at?->format('Y-m-d H:i'),
            'done' => $entry->done_at !== null,
            'done_on' => $entry->done_at?->setTimezone($this->zone())->toDateString(),
            'revision' => (int) $entry->revision,
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }
}
