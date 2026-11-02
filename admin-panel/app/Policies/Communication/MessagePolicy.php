<?php

declare(strict_types=1);

namespace App\Policies\Communication;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\ClassSection;
use App\Models\Communication\Audience;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRecipient;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Communication\CommunicationRecipients;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\RecipientAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MessagePolicy
{
    /** Standard scope authority is structural, independent of recipient count. */
    public function scope(User $actor, array $definition, string $date, bool $review = false): void
    {
        $rules = app(CommunicationRules::class);
        $rules->staff($actor);
        $year = SessionYear::findOrFail((int) $definition['session_year_id']);
        $wide = $rules->manages($actor) || $rules->principal($actor) || (! $review && $rules->effective($actor)['allow_school']);
        $kind = $definition['kind'];
        if (app(CommunityAuthority::class)->facilitatorOnly($actor)) {
            abort_unless(in_array($kind, ['activity_group', 'custom'], true), 403);
        }
        $target = (int) ($definition['target_id'] ?? 0);
        if ($kind === 'school') {
            abort_unless($wide, 403);

            return;
        }
        if ($kind === 'custom' && isset($definition['smart'])) {
            // A smart group: the author (or reviewer) needs authority over every rule.
            abort_unless((int) $definition['smart']['session_year_id'] === (int) $year->id, 422);
            foreach ($definition['smart']['rules'] as $rule) {
                $this->scope($actor, ['kind' => $rule['kind'], 'target_id' => (int) $rule['target_id'], 'session_year_id' => (int) $year->id], $date, $review);
            }

            return;
        }
        if ($kind === 'custom') {
            $allowed = app(CommunicationRecipients::class)->allowed($actor, (int) $year->id, $date, $review);
            abort_unless(array_diff($definition['user_ids'], array_keys($allowed)) === [], 403);

            return;
        }
        if ($kind === 'grade') {
            GradeLevel::findOrFail($target);
            abort_unless($wide || in_array($target, $rules->heads($actor), true), 403);

            return;
        }
        if ($kind === 'activity_group') {
            $group = ActivityGroup::where('session_year_id', $year->id)->where('active', true)->findOrFail($target);
            app(CommunityAuthority::class)->audience($actor, (int) $group->id);

            return;
        }
        if ($kind === 'group') {
            $group = TeachingGroup::where('session_year_id', $year->id)->where('active', true)->findOrFail($target);
            if ($wide || in_array((int) $group->grade_level_id, $rules->heads($actor), true)) {
                return;
            }
            abort_if($review, 403);
            abort_unless(TeachingAllocation::where('teaching_group_id', $group->id)->whereIn('teacher_id', app(AcademicAuthority::class)->teachers($actor))
                ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists(), 403);

            return;
        }
        abort_unless($kind === 'homeroom', 422);
        $homeroom = ClassSection::with('class')->findOrFail($target);
        if ($wide || in_array((int) $homeroom->class?->grade_level_id, $rules->heads($actor), true)) {
            return;
        }
        abort_if($review, 403);
        abort_unless(HomeroomAllocation::where('class_section_id', $target)->where('session_year_id', $year->id)->whereIn('teacher_id', app(AcademicAuthority::class)->teachers($actor))
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists(), 403);
    }

    public function snapshot(User $actor, array $input, string $date): array
    {
        $kind = $input['kind'];
        $definition = ['kind' => $kind, 'session_year_id' => (int) $input['session_year_id'],
            'target_id' => $kind === 'school' ? null : (int) $input['target_id'], 'parents' => (bool) ($input['parents'] ?? false)];
        if ($kind === 'custom') {
            $list = Audience::where('active', true)->findOrFail($definition['target_id']);
            $mine = (int) $list->created_by === (int) $actor->id || app(CommunicationRules::class)->manages($actor);
            abort_unless($mine || $list->shared, 404);
            if ($list->isSmart()) {
                // Rules, not people: resolved again at preview and at sending.
                abort_unless((int) $list->definition['session_year_id'] === $definition['session_year_id'], 404);
                $definition += ['name' => $list->name, 'audience_revision' => $list->revision, 'smart' => $list->definition];
                $definition['parents'] = (bool) $list->definition['parents'];
            } else {
                $allowed = app(CommunicationRecipients::class)->allowed($actor, $definition['session_year_id'], $date);
                $ids = $list->definition['user_ids'];
                abort_unless($mine || array_diff($ids, array_keys($allowed)) === [], 404);
                $definition += ['user_ids' => array_values(array_intersect($ids, array_keys($allowed))), 'name' => $list->name, 'audience_revision' => $list->revision];
                $definition['parents'] = false;
            }
        }
        $this->scope($actor, $definition, $date);

        return $definition;
    }

    public function recipients(User $author, array $definition, string $date): array
    {
        $this->scope($author, $definition, $date);
        $resolver = app(CommunicationRecipients::class);
        if ($definition['kind'] === 'custom' && isset($definition['smart'])) {
            return $resolver->smart($definition['smart'], $date);
        }
        if ($definition['kind'] === 'custom') {
            return array_intersect_key($resolver->allowed($author, $definition['session_year_id'], $date), array_flip($definition['user_ids']));
        }

        return $resolver->standard($definition, $date);
    }

    public function canReview(User $actor, Message $message): bool
    {
        if ((int) $message->author_id === (int) $actor->id) {
            return false;
        }
        if ($message->submitted_at === null || $message->state === 'draft') {
            return false;
        }
        try {
            $rules = app(CommunicationRules::class);
            $rules->staff($actor);
            if (! $rules->manages($actor) && ! $rules->principal($actor) && $rules->heads($actor) === []) {
                return false;
            }
            $this->scope($actor, $message->audience, app(AcademicAuthority::class)->today(), true);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return false;
        }
    }

    public function staff(User $actor, int $id, bool $owner = false): Message
    {
        app(CommunicationRules::class)->staff($actor);
        $message = Message::findOrFail($id);
        abort_unless((int) $message->author_id === (int) $actor->id || (! $owner && $this->canReview($actor, $message)), 404);

        return $message;
    }

    public function reader(User $actor, int $id): Message
    {
        app(CommunicationRules::class)->actor($actor);
        $recipient = MessageRecipient::where('message_id', $id)->where('user_id', $actor->id)->firstOrFail();
        $reason = $recipient->access_reason;
        abort_unless(is_array($reason) && app(RecipientAccess::class)->allows($actor, $reason), 404);

        return Message::whereIn('state', ['dispatching', 'sent'])->whereNotNull('published_at')->findOrFail($id);
    }
}
