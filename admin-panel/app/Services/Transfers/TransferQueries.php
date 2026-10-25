<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Academics\ReportCard;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Transitions\ExitEvidence;
use App\Services\Gallery\GalleryTransfers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransferQueries
{
    public function access(User $actor): array
    {
        $a = app(TransferAuthority::class);
        $fresh = $a->actor($actor);
        $staff = false;
        try {
            $a->staff($fresh);
            $staff = true;
        } catch (HttpException $e) {
            abort_unless($e->getStatusCode() === 403, $e->getStatusCode());
        }
        $parent = $fresh->email_verified_at !== null && in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true);
        abort_unless($staff || $parent, 403);

        return ['staff' => $staff, 'guardian' => $parent, 'can_write' => app(ModuleGate::class)->writable('school_transfers'), 'consent_version' => (string) config('school_transfers.consent_version'), 'today' => app(AcademicAuthority::class)->today()];
    }

    /** Bound selections independently so a selected item survives unrelated pagination. */
    public function choices(User $actor, string $kind, string $search = '', int $page = 1, ?int $learner = null, ?int $guardian = null, ?string $selected = null): array
    {
        app(TransferAuthority::class)->staff($actor);
        $q = match ($kind) {
            'learners' => Students::with('user')->whereHas('user'),
            'destinations' => School::active()->where('id', '!=', app(CurrentSchool::class)->id()),
            'years' => SessionYear::query(),
            'rooms' => ClassSection::with('class', 'section')->whereHas('class', fn ($q) => $q->whereNotNull('grade_level_id')),
            'categories' => Category::query(),
            'guardians', 'reports', 'documents' => null,
            default => abort(404),
        };
        if (in_array($kind, ['guardians', 'reports', 'documents'], true)) {
            $child = Students::with('user')->findOrFail($learner);
            if ($kind === 'guardians') {
                $ids = GuardianLink::where('learner_id', $child->id)->where('status', 'active')->where('can_approve', true)->pluck('guardian_user_id');
                $q = User::whereIn('id', $ids)->whereNotNull('email_verified_at');
            } elseif ($kind === 'reports') {
                $q = ReportCard::with('batch')->where('learner_id', $child->id)->whereHas('batch', fn ($q) => $q->where('state', 'released')->whereColumn('report_batches.fingerprint', 'report_cards.generated_fingerprint'));
            } else {
                $parent = User::findOrFail($guardian);
                app(TransferAuthority::class)->guardian($parent, (int) $child->id);
                $rows = [];
                foreach (app(ExitEvidence::class)->documents($actor, $child) as $d) {
                    try {
                        $allowed = app(TransferEvidence::class)->documents($actor, $parent, $child, [$d['id']]);
                        $rows[] = ['key' => (string) $d['id'], 'label' => $d['name'], 'detail' => (string) ($allowed[0]['label'] ?? '')];
                    } catch (HttpException $e) {
                        if (! in_array($e->getStatusCode(), [403, 404], true)) {
                            throw $e;
                        }
                    }
                }
                $rows = array_values(array_filter($rows, fn ($d) => ($selected === null || $d['key'] === $selected) && ($search === '' || mb_stripos($d['label'], $search) !== false)));
                $offset = (max(1, $page) - 1) * 25;

                return ['items' => array_slice($rows, $offset, 25), 'page' => max(1, $page), 'has_more' => count($rows) > $offset + 25];
            }
        }
        if ($selected !== null) {
            $q->whereKey($selected);
        }
        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';
            if ($kind === 'learners') {
                $q->whereHas('user', fn ($u) => $this->nameSearch($u, $like));
            } elseif ($kind === 'guardians') {
                $this->nameSearch($q, $like);
            } elseif (in_array($kind, ['destinations', 'years', 'categories'], true)) {
                $q->where('name', 'like', $like);
            } elseif ($kind === 'rooms') {
                $q->whereHas('class', fn ($c) => $c->where('name', 'like', $like));
            }
        }
        $rows = $q->orderBy('id')->offset((max(1, $page) - 1) * 25)->limit(26)->get();
        $items = $rows->take(25)->map(fn ($row) => ['key' => (string) $row->id, 'label' => match ($kind) {
            'learners' => $row->user->full_name, 'guardians' => $row->full_name,
            'reports' => $row->payload['year_name'].' · '.$row->payload['term_name'],
            'rooms' => $row->class->name.' · '.($row->section?->name ?? ''), default => $row->name,
        }, 'detail' => $kind === 'learners' ? (string) $row->student_number : ''])->values()->all();

        return ['items' => $items, 'page' => max(1, $page), 'has_more' => $rows->count() > 25];
    }

    private function nameSearch(Builder $q, string $like): void
    {
        $q->where(fn ($u) => $u->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like));
    }

    public function listing(User $actor, string $lane, int $page = 1): array
    {
        $a = app(TransferAuthority::class);
        $access = $this->access($actor);
        if ($lane === 'consents') {
            abort_unless($access['guardian'], 403);
            $q = TransferPackage::where('guardian_id', $actor->id)->whereIn('learner_id', GuardianLink::where('guardian_user_id', $actor->id)->where('status', 'active')->where('can_approve', true)->select('learner_id'));
        } else {
            $a->staff($actor);
            $q = match ($lane) {
                'outgoing' => TransferPackage::query(),
                'incoming' => TransferPackage::acrossSchools()->where('to_school_id', app(CurrentSchool::class)->id())->where('state', 'released')->where('expires_at', '>', now()->utc()),
                'receipts' => TransferReceipt::query(), default => abort(404),
            };
        }
        $rows = $q->orderByDesc('id')->offset((max(1, $page) - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            if ($lane === 'incoming') {
                try {
                    app(CurrentSchool::class)->run((int) $row->school_id, fn () => app(TransferPackages::class)->sourceCurrent($row));
                } catch (HttpException|ModelNotFoundException $e) {
                    if ($e instanceof HttpException && ! in_array($e->getStatusCode(), [403, 404, 409], true)) {
                        throw $e;
                    }

                    continue;
                }
            }
            $items[] = $this->summary($row);
        }
        $this->access($actor);

        return ['items' => $items, 'page' => max(1, $page), 'has_more' => $rows->count() > 25];
    }

    public function summary(TransferPackage|TransferReceipt $row): array
    {
        $c = $row->contents;
        $received = $row instanceof TransferReceipt;

        return ['id' => (int) $row->id, 'public_id' => $received ? (string) (json_decode($row->manifest, true, flags: JSON_THROW_ON_ERROR)['package_id']) : $row->public_id,
            'state' => $received ? 'accepted' : $row->state, 'revision' => $received ? 1 : (int) $row->revision,
            'fingerprint' => $received ? hash('sha256', $row->manifest) : $row->fingerprint, 'learner_name' => $c['learner_name'], 'student_number' => $c['student_number'],
            'guardian_name' => $c['guardian_name'], 'from_school_name' => $c['from_school_name'], 'to_school_name' => $c['to_school_name'],
            'last_attended_on' => $c['last_attended_on'], 'starts_on' => $c['starts_on'], 'expires_at' => $received ? null : $row->expires_at->toISOString(),
            'export_only' => (bool) $c['export_only'], 'report_count' => count($c['reports']), 'document_count' => count($c['documents']),
            'mark_count' => count($c['released_marks']), 'attendance_count' => count($c['attendance'])];
    }

    public function record(User $actor, string $lane, int $id): TransferPackage|TransferReceipt
    {
        $a = app(TransferAuthority::class);

        return match ($lane) {
            'outgoing' => $a->outgoing($actor, $id), 'consents' => $a->ownConsent($actor, $id),
            'incoming' => $a->incoming($actor, $id), 'receipts' => $a->receipt($actor, $id), default => abort(404),
        };
    }

    public function show(User $actor, string $lane, int $id): array
    {
        $row = $this->record($actor, $lane, $id);
        if ($lane === 'incoming') {
            abort_unless($row->state === 'released', 404);
            app(CurrentSchool::class)->run((int) $row->school_id, fn () => app(TransferPackages::class)->sourceCurrent($row));
        }
        $c = $row->contents;
        $writable = app(ModuleGate::class)->writable('school_transfers');
        $open = $row instanceof TransferPackage && $row->expires_at->isFuture() && ! in_array($row->state, ['accepted', 'cancelled', 'expired'], true);
        $externalDone = $row instanceof TransferPackage && $row->to_school_id === null && $row->released_at !== null;
        $result = $this->summary($row) + ['consent_version' => (string) config('school_transfers.consent_version'),
            'reports' => $c['reports'], 'documents' => $c['documents'], 'released_marks' => $c['released_marks'], 'attendance' => $c['attendance'],
            'can_consent' => $writable && $lane === 'consents' && $open && $row->state === 'awaiting_consent',
            'can_withdraw' => $writable && $lane === 'consents' && $open && ! $externalDone,
            'can_release' => $writable && $lane === 'outgoing' && $open && $row->state === 'consented',
            'can_cancel' => $writable && $lane === 'outgoing' && $open && ! $externalDone,
            'can_accept' => $writable && $lane === 'incoming' && $open && $row->state === 'released',
            'can_download' => $row instanceof TransferReceipt || ($open || $externalDone) && $row->expires_at->isFuture()];
        if ($lane === 'consents' && $result['can_consent'] && app(ModuleGate::class)->allows('gallery')) {
            try {
                $result['gallery_choice'] = app(GalleryTransfers::class)->preview($actor, $row);
            } catch (HttpException|ModelNotFoundException $error) {
                // An unavailable private-gallery choice never grants access or
                // blocks the independent academic consent workflow.
                if ($error instanceof HttpException && ! in_array($error->getStatusCode(), [403, 404, 409, 422], true)) {
                    throw $error;
                }
            }
        }
        $this->record($actor, $lane, $id);

        return $result;
    }
}
