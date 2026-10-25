<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\ClassSection;
use App\Models\Communication\Audience;
use App\Models\Consultations\ConfigurationEvent;
use App\Models\Consultations\Consultation;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Communication\CommunicationRules;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ConsultationQueries
{
    public function __construct(private ConsultationAccess $access, private Consultations $service) {}

    public function access(User $actor): array
    {
        $fresh = $this->access->actor($actor);
        $manager = false;
        try {
            $this->access->manager($fresh);
            $manager = true;
        } catch (ModelNotFoundException) {
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }
        }

        return ['school_id' => app(CurrentSchool::class)->id(), 'account_id' => (int) $fresh->id, 'session_year_id' => (int) schoolSetting('session_year'),
            'today' => app(AcademicAuthority::class)->today(), 'timezone' => app(CurrentSchool::class)->school()->timezone,
            'writable' => app(ModuleGate::class)->writable('consultations'), 'can_manage' => $manager];
    }

    public function listing(User $actor, int $year, int $page = 1, bool $managed = false): array
    {
        $managed ? $this->access->manager($actor) : $this->access->actor($actor);
        $this->access->year($year);
        abort_unless($page >= 1 && $page <= 100000, 422);
        $source = Consultation::where('session_year_id', $year)->orderByDesc('id');
        $ids = [];
        if (! $managed) {
            $source->whereNotIn('status', ['draft', 'cancelled']);
        }
        // Audience metadata first; titles/instructions are loaded only for an authorized page.
        foreach ($source->select(['id', 'school_id', 'session_year_id', 'status', 'audience'])->cursor() as $row) {
            if ($managed || $this->access->eligible($actor, $row)) {
                $ids[] = (int) $row->id;
            }
        }
        $last = max(1, (int) ceil(count($ids) / 20));
        $selected = array_slice($ids, ($page - 1) * 20, 20);
        $rows = Consultation::whereIntegerInRaw('id', $selected)->orderByDesc('id')->get();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->service->listingItem($actor, $row, $managed);
        }

        return ['items' => $items, 'page' => $page, 'last_page' => $last];
    }

    /** Named school-scoped targets; delegated managers see only their own custom lists. */
    public function audiences(User $actor, int $year, string $kind, string $search = '', int $page = 1, ?int $selected = null): array
    {
        $this->access->manager($actor);
        $this->access->year($year);
        abort_unless(in_array($kind, ['grade', 'homeroom', 'group', 'activity_group', 'custom'], true) && $page >= 1 && $page <= 100000, 422);
        $query = match ($kind) {
            'grade' => GradeLevel::query(),
            'homeroom' => ClassSection::with(['class', 'section']),
            'group' => TeachingGroup::where('session_year_id', $year)->where('active', true),
            'activity_group' => ActivityGroup::where('session_year_id', $year)->where('active', true),
            'custom' => Audience::where('active', true),
        };
        if ($kind === 'activity_group' && ! app(ModuleGate::class)->allows('groups')) {
            $query->whereRaw('1 = 0');
        }
        if ($kind === 'custom' && ! app(CommunicationRules::class)->manages($actor)) {
            $query->where('created_by', $actor->id);
        }
        $label = static fn ($row) => $kind === 'homeroom'
            ? trim(($row->class?->name ?? '').' '.($row->section?->name ?? '')) : (string) $row->name;
        $matches = $query->orderBy('id')->get()->map(fn ($row) => ['id' => (int) $row->id, 'name' => $label($row) ?: 'Homeroom #'.$row->id]);
        $selectedRow = $selected === null ? null : $matches->firstWhere('id', $selected);
        if ($search !== '') {
            $matches = $matches->filter(fn ($row) => mb_stripos($row['name'], $search) !== false)->values();
        }

        return ['items' => $matches->slice(($page - 1) * 25, 25)->values()->all(), 'page' => $page, 'last_page' => max(1, (int) ceil($matches->count() / 25)),
            'selected_id' => $selectedRow['id'] ?? null, 'selected_name' => $selectedRow['name'] ?? null];
    }

    private function historyItem(ConfigurationEvent $event): array
    {
        $actor = $event->actor_id === null ? null : User::find($event->actor_id);

        return ['id' => (int) $event->id, 'action' => $event->action, 'revision' => (int) $event->revision,
            'actor_id' => $event->actor_id === null ? null : (int) $event->actor_id, 'actor_name' => $actor?->full_name,
            'created_at' => $event->created_at->toISOString()];
    }

    public function history(User $actor, int $id, int $page = 1): array
    {
        $this->access->manager($actor);
        Consultation::findOrFail($id);
        abort_unless($page >= 1 && $page <= 100000, 422);
        $rows = ConfigurationEvent::where('consultation_id', $id)->orderByDesc('id')->paginate(25, ['id', 'action', 'revision', 'actor_id', 'created_at'], 'page', $page);

        return ['items' => $rows->map(fn ($event) => $this->historyItem($event))->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function historyEntry(User $actor, int $id, int $event): array
    {
        $this->access->manager($actor);
        Consultation::findOrFail($id);
        $row = ConfigurationEvent::where('consultation_id', $id)->findOrFail($event);

        return $this->historyItem($row) + ['snapshot' => $row->snapshot];
    }
}
