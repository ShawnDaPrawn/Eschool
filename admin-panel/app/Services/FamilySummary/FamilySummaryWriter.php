<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Family\FamilyDailySummaryPart;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The words of one school's part (Action Plan 5.11). The AI writes the
 * children's lines through the gateway's family_summary policy, from the
 * model input below only; when the AI is off, capped, failing or its answer
 * is refused, a plain template says the same thing. The waiting-messages and
 * private-update lines are always the template's own, so "you have 1 private
 * update" is never reworded.
 */
class FamilySummaryWriter
{
    public const FEATURE = 'family_summary';

    public const PROMPT = 'family-summary';

    /**
     * Exactly what the model sees: first names and the day's school facts.
     * No counts of private updates, no school ids, learner ids or dates of birth.
     */
    public static function modelInput(array $facts): string
    {
        $children = [];
        foreach ($facts['children'] ?? [] as $child) {
            $children[] = [
                'first_name' => (string) $child['first_name'],
                'timetable' => array_map(fn (array $c) => array_intersect_key($c, array_flip(['day', 'kind', 'subject', 'period', 'pattern'])), $child['timetable'] ?? []),
                'homework' => array_map(fn (array $h) => array_intersect_key($h, array_flip(['day', 'subject', 'title', 'time'])), $child['homework'] ?? []),
                'forms' => array_map(fn (array $f) => array_intersect_key($f, array_flip(['title', 'reply_by'])), $child['forms'] ?? []),
            ];
        }

        return json_encode(['language' => app()->getLocale(), 'school' => (string) ($facts['school'] ?? ''), 'children' => $children],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array{text: string, wording: string, reason: ?string} */
    public function write(User $guardian, FamilyDailySummaryPart $part): array
    {
        $facts = $part->facts;
        $footer = $this->footer($facts);
        if (! FamilySummaryFacts::hasChildItems($facts)) {
            return ['text' => $this->join([$this->heading($facts), ...$this->quietChildren($facts), ...$footer]), 'wording' => 'template', 'reason' => 'nothing_to_write'];
        }
        try {
            $response = app(AiGateway::class)->runFeature($guardian, Capability::Complete,
                new FeatureInferenceRequest(self::FEATURE, (string) $part->operation_id, ['part_id' => (int) $part->id]));
            $text = FamilySummaryModeration::clean($response->output);
            if ($text === null) {
                throw new AiUnavailable('family_summary_output_invalid');
            }

            return ['text' => $this->join([$this->heading($facts), $text, ...$footer]), 'wording' => 'ai', 'reason' => null];
        } catch (AiUnavailable $error) {
            $reason = $error->reason;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $reason = 'inference_refused';
        } catch (Throwable $error) {
            Log::warning('family_summary_ai_fallback', ['exception_class' => $error::class]);
            $reason = 'inference_unavailable';
        }

        return ['text' => $this->template($facts), 'wording' => 'template', 'reason' => substr($reason, 0, 64)];
    }

    /** The plain wording, used whenever the AI is not. */
    public function template(array $facts): string
    {
        $lines = [$this->heading($facts)];
        foreach ($facts['children'] ?? [] as $child) {
            $items = [];
            foreach ($child['timetable'] ?? [] as $change) {
                $day = $this->day($change['day']);
                $items[] = match ($change['kind']) {
                    'short_day' => __(':day: the school follows the :pattern bells.', ['day' => $day, 'pattern' => $change['pattern'] ?? __('short day')]),
                    'cover' => __(':day: :subject (:period) has a different teacher.', ['day' => $day, 'subject' => $change['subject'] ?: __('a lesson'), 'period' => $change['period'] ?? '']),
                    default => __(':day: exam day.', ['day' => $day]),
                };
            }
            foreach ($child['homework'] ?? [] as $homework) {
                $items[] = __(':day: :subject homework due, :title.', ['day' => $this->day($homework['day']), 'subject' => $homework['subject'] ?: __('School'), 'title' => $homework['title']]);
            }
            foreach ($child['forms'] ?? [] as $form) {
                $items[] = __('A form is waiting for you: :title. Please reply by :date.', ['title' => $form['title'], 'date' => $this->date($form['reply_by'])]);
            }
            $lines[] = $items === [] ? __('Nothing new for :name today.', ['name' => $child['first_name']])
                : $child['first_name']."\n".implode("\n", array_map(fn ($item) => '• '.$item, $items));
        }

        return $this->join([...$lines, ...$this->footer($facts)]);
    }

    private function heading(array $facts): string
    {
        return (string) ($facts['school'] ?? '');
    }

    /** @return list<string> */
    private function quietChildren(array $facts): array
    {
        return array_map(fn (array $child) => __('Nothing new for :name today.', ['name' => $child['first_name']]), $facts['children'] ?? []);
    }

    /** @return list<string> */
    private function footer(array $facts): array
    {
        $lines = [];
        $messages = (int) ($facts['unread_messages'] ?? 0);
        if ($messages > 0) {
            $lines[] = $messages === 1 ? __('You have 1 unread school message.') : __('You have :count unread school messages.', ['count' => $messages]);
        }
        $private = (int) ($facts['private_updates'] ?? 0);
        if ($private > 0) {
            $lines[] = $private === 1 ? __('You have 1 private update.').' '.__('Open your notifications to read it.')
                : __('You have :count private updates.', ['count' => $private]).' '.__('Open your notifications to read them.');
        }

        return $lines;
    }

    private function day(string $day): string
    {
        return $day === 'today' ? __('Today') : __('Tomorrow');
    }

    private function date(string $date): string
    {
        try {
            return CarbonImmutable::parse($date)->locale(app()->getLocale())->isoFormat('D MMMM');
        } catch (Throwable) {
            return $date;
        }
    }

    private function join(array $lines): string
    {
        return implode("\n\n", array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== '')));
    }
}
