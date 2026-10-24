<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSection;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRead;
use App\Models\Communication\MessageRecipient;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MessageQueries
{
    public function configuration(User $actor, ?int $year): array
    {
        $fresh = app(CommunicationRules::class)->actor($actor);
        $staff = array_diff(app(ProfilePolicy::class)->roles($fresh), ['Student', 'Parent']) !== [];
        $school = app(CurrentSchool::class);
        $data = ['school_id' => $school->id(), 'reader_id' => (int) $actor->id, 'timezone' => $school->school()->timezone,
            'staff_available' => $staff, 'can_manage' => false, 'session_year_id' => null, 'years' => [], 'scopes' => [], 'audiences' => [], 'rules' => null,
            'channels' => ['in_app', 'push', 'email']];
        if (! $staff) {
            return $data;
        }
        $year = $year ?? (int) schoolSetting('session_year');
        SessionYear::findOrFail($year);
        $data['session_year_id'] = $year;
        $data['years'] = SessionYear::orderByDesc('id')->get(['id', 'name'])->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all();
        $data['can_manage'] = app(CommunicationRules::class)->manages($actor);
        $data['rules'] = app(CommunicationRules::class)->effective($actor);
        $today = app(AcademicAuthority::class)->today();
        $scopes = [];
        $add = function (string $kind, ?int $id, string $label) use (&$scopes, $actor, $year, $today) {
            try {
                app(MessagePolicy::class)->scope($actor, ['kind' => $kind, 'target_id' => $id, 'session_year_id' => $year], $today);
                $scopes[] = ['kind' => $kind, 'target_id' => $id, 'name' => $label];
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }
            }
        };
        $add('school', null, __('Whole school'));
        foreach (GradeLevel::orderBy('sort_order')->get() as $grade) {
            $add('grade', (int) $grade->id, $grade->name);
        }
        foreach (ClassSection::with('class', 'section')->orderBy('id')->get() as $home) {
            $add('homeroom', (int) $home->id, trim(($home->class?->name ?? '').' '.($home->section?->name ?? '')));
        }
        foreach (TeachingGroup::where('active', true)->where('session_year_id', $year)->orderBy('id')->get() as $group) {
            $add('group', (int) $group->id, $group->name);
        }
        $data['scopes'] = $scopes;
        $data['audiences'] = app(CommunicationAudiences::class)->listing($actor, $year);

        return $data;
    }

    public function contacts(User $actor, int $year, int $page): array
    {
        $allowed = app(CommunicationRecipients::class)->allowed($actor, $year, app(AcademicAuthority::class)->today());
        $query = app(CommunicationRecipients::class)->members()->whereIn('id', array_keys($allowed))->orderBy('id');
        $rows = $query->paginate(50, ['id', 'first_name', 'last_name'], 'page', $page);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->map(fn ($user) => ['id' => (int) $user->id, 'name' => trim($user->first_name.' '.$user->last_name)])->all()];
    }

    private function summary(Message $message): array
    {
        return ['id' => (int) $message->id, 'title' => $message->title, 'state' => $message->state, 'revision' => $message->revision,
            'published_at' => $message->published_at?->toIso8601String(), 'scheduled_at' => $message->scheduled_at?->toIso8601String(),
            'updated_at' => $message->updated_at->toIso8601String()];
    }

    public function editor(User $actor, int $id): array
    {
        $m = app(MessagePolicy::class)->staff($actor, $id);

        return $this->summary($m) + ['markdown' => $m->markdown, 'audience' => $m->audience, 'channels' => $m->channels,
            'scheduled_local' => $m->scheduled_at?->copy()->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i'),
            'requires_approval' => $m->requires_approval, 'submitted_at' => $m->submitted_at?->toIso8601String(), 'approved_at' => $m->approved_at?->toIso8601String(),
            'decision_reason' => $m->decision_reason, 'blocked_code' => $m->blocked_code, 'withdrawal_reason' => $m->withdrawal_reason,
            'is_author' => (int) $m->author_id === (int) $actor->id, 'can_review' => app(MessagePolicy::class)->canReview($actor, $m),
            'recipient_count' => $m->recipient_count, 'read_count' => MessageRead::where('message_id', $id)->count()];
    }

    public function drafts(User $actor, int $page): array
    {
        app(CommunicationRules::class)->staff($actor);
        // Count only the candidate pages, never leak private subjects of other authors.
        $query = Message::where(fn ($q) => $q->where('author_id', $actor->id)->orWhereIn('state', ['pending', 'approved', 'blocked']))->orderByDesc('id');
        $rows = $query->paginate(20, ['*'], 'page', $page);
        $items = $rows->filter(fn ($m) => (int) $m->author_id === (int) $actor->id || app(MessagePolicy::class)->canReview($actor, $m))
            ->map(fn ($m) => $this->summary($m) + ['is_author' => (int) $m->author_id === (int) $actor->id, 'can_review' => app(MessagePolicy::class)->canReview($actor, $m)])->values()->all();

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $items];
    }

    public function inbox(User $actor, int $page): array
    {
        app(CommunicationRules::class)->actor($actor);
        $ids = MessageRecipient::where('user_id', $actor->id)->select('message_id');
        $rows = Message::whereIn('id', $ids)->whereIn('state', ['dispatching', 'sent'])->whereNotNull('published_at')->orderByDesc('id')->paginate(20, ['*'], 'page', $page);
        $read = MessageRead::where('user_id', $actor->id)->whereIn('message_id', $rows->pluck('id'))->pluck('read_at', 'message_id');
        $items = [];
        foreach ($rows as $m) {
            try {
                app(MessagePolicy::class)->reader($actor, (int) $m->id);
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }

                continue;
            }
            $items[] = $this->summary($m) + ['read_at' => $read->get($m->id)?->toIso8601String()];
        }

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $items];
    }

    public function reader(User $actor, int $id): array
    {
        $m = app(MessagePolicy::class)->reader($actor, $id);

        return $this->summary($m) + ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id,
            'markdown' => $m->markdown, 'read_at' => MessageRead::where('message_id', $id)->where('user_id', $actor->id)->first()?->read_at?->toIso8601String()];
    }

    public function read(User $actor, int $id): array
    {
        app(MessagePolicy::class)->reader($actor, $id);
        $row = MessageRead::firstOrCreate(['message_id' => $id, 'user_id' => $actor->id], ['read_at' => now()]);

        return ['id' => $id, 'read_at' => $row->read_at->toIso8601String()];
    }
}
