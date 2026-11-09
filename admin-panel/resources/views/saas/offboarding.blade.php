@extends('saas.layout')

@section('title', __('Offboarding').' · '.$school['name'])

@use('App\Models\Platform\SchoolOffboarding')
@php
    $platformCan = fn (string $ability): bool => app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), $ability);
    $o = $offboarding;
    $manage = $platformCan('offboarding.manage');
    $approve = $platformCan('offboarding.approve');
    $kb = fn (?int $bytes): string => $bytes === null ? '—' : number_format($bytes / 1024, 1).' KB';
@endphp

@section('content')
<p><a href="{{ route('saas.schools.show', $school['id']) }}">{{ $school['name'] }}</a></p>
<h1>{{ __('Offboarding') }} · {{ $school['name'] }}</h1>
<p class="saas-lead">{{ __('A leaving school first gets all of its own data back, then is archived, and its data is deleted when the contract says. Navigate Ed is the operator and the school the responsible party. This page shows states and counts only: HQ never opens the school\'s data or its export.') }}</p>

@if ($o === null)
    <h2>{{ __('Start an offboarding') }}</h2>
    @if (! $manage)
        <p class="saas-card">{{ __('This school is not being offboarded.') }}</p>
    @else
    <form method="POST" action="{{ route('saas.schools.offboarding.start', $school['id']) }}" class="saas-card" aria-label="{{ __('Start an offboarding') }}">
        @csrf
        <p>{{ __('Active Super Admins who will receive the export: :count', ['count' => $administrators]) }}</p>
        @if ($contract !== null && $contract['delete_on'] !== null)
            <p>{{ __('Retention terms from the contract from :starts: data deleted :days days after it ends on :ends, so on :date.', ['starts' => $contract['starts_on'], 'days' => $contract['deletion_days_after_end'], 'ends' => $contract['ends_on'], 'date' => $contract['delete_on']]) }}</p>
        @else
            <p>{{ __('The contract records no deletion term. Enter the date and the terms you are following, or leave both empty to use Navigate Ed\'s default: :days days after the school\'s deadline to collect its export.', ['days' => $defaultRetentionDays]) }}</p>
        @endif
        <div class="saas-grid">
            <div class="saas-field"><label for="reason">{{ __('Reason (why the school is leaving)') }}</label><textarea id="reason" name="reason" required minlength="10" maxlength="500" rows="2">{{ old('reason') }}</textarea></div>
            <div class="saas-field"><label for="receipt_deadline">{{ __('Deadline for the school to collect and confirm its export') }}</label><input id="receipt_deadline" name="receipt_deadline" type="date" min="{{ $minimumDeadline }}" required value="{{ old('receipt_deadline') }}"></div>
            @if ($contract === null || $contract['delete_on'] === null)
                <div class="saas-field"><label for="delete_on">{{ __('Delete the school\'s data on (optional)') }}</label><input id="delete_on" name="delete_on" type="date" value="{{ old('delete_on') }}"></div>
                <div class="saas-field"><label for="retention_terms">{{ __('Retention terms followed, for example the contract clause (optional)') }}</label><textarea id="retention_terms" name="retention_terms" minlength="10" maxlength="500" rows="2">{{ old('retention_terms') }}</textarea></div>
            @endif
        </div>
        <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Start offboarding and prepare the export') }}</button></div>
    </form>
    @endif
@else
    <h2>{{ $o['reference'] }}</h2>
    <div class="saas-card">
        <p><span class="saas-badge {{ $o['legal_hold'] ? 'saas-badge--warn' : '' }}">{{ __($o['state_label']) }}</span>@if ($o['legal_hold']) <span class="saas-badge saas-badge--warn">{{ __('Legal hold') }}</span>@endif</p>
        <dl class="saas-grid">
            <div><dt>{{ __('Started') }}</dt><dd>{{ $o['started_at'] }}@if ($o['started_by']) · {{ $o['started_by'] }}@endif</dd></div>
            <div><dt>{{ __('Reason') }}</dt><dd>{{ $o['reason'] }}</dd></div>
            <div><dt>{{ __('Retention terms') }}</dt><dd>{{ match ($o['retention_source']) { 'contract' => __('From the contract'), 'default' => __('Navigate Ed\'s default'), default => __('Entered') } }}: {{ $o['retention_terms'] }}</dd></div>
            <div><dt>{{ __('Receipt deadline') }}</dt><dd>{{ $o['receipt_deadline'] }}</dd></div>
            <div><dt>{{ __('Data deleted on') }}</dt><dd>{{ $o['delete_on'] }}</dd></div>
        </dl>
    </div>

    <h2>{{ __('1. Export for the school') }}</h2>
    <div class="saas-card">
        @if ($o['state'] === SchoolOffboarding::PREPARING)
            <p>{{ __('The export is being prepared.') }}</p>
        @elseif ($o['state'] === SchoolOffboarding::FAILED)
            <p>{{ __('The export could not be prepared (:code).', ['code' => $o['failure_code']]) }}</p>
            @if ($manage)
                <form method="POST" action="{{ route('saas.schools.offboarding.retry', $school['id']) }}">@csrf<button class="saas-btn" type="submit">{{ __('Prepare the export again') }}</button></form>
            @endif
        @else
            <p>{{ __('Ready :at: :tables tables, :rows rows, :size. Only the school\'s Super Admins can download it, from their own panel.', ['at' => $o['export_ready_at'], 'tables' => $o['tables'], 'rows' => $o['rows'], 'size' => $kb($o['bytes'])]) }}</p>
            <p>{{ __('SHA-256') }}: <code>{{ $o['sha256'] }}</code></p>
            <p>{{ trans_choice('{0} Not downloaded yet.|{1} Downloaded once, last :at.|[2,*] Downloaded :count times, last :at.', $o['downloads'], ['count' => $o['downloads'], 'at' => $o['last_downloaded_at']]) }}</p>
        @endif
    </div>

    <h2>{{ __('2. Receipt') }}</h2>
    <p class="saas-card">
        @if ($o['received_at'] === null)
            {{ __('Not confirmed yet. If the school does not confirm by :date, the deadline counts as receipt.', ['date' => $o['receipt_deadline']]) }}
        @elseif ($o['receipt_basis'] === 'confirmed')
            {{ __('Confirmed by the school\'s Super Admin :at.', ['at' => $o['received_at']]) }}
        @else
            {{ __('The deadline passed :at without confirmation; counted as received.', ['at' => $o['received_at']]) }}
        @endif
    </p>

    <h2>{{ __('3. Archive') }}</h2>
    <div class="saas-card">
        @if ($o['archived_at'])
            <p>{{ __('Archived :at by :name. Its people cannot sign in. Deletion is scheduled for :date.', ['at' => $o['archived_at'], 'name' => $o['archived_by'] ?? __('System'), 'date' => $o['delete_on']]) }}</p>
        @elseif ($o['state'] === SchoolOffboarding::RECEIVED && $manage)
            <form method="POST" action="{{ route('saas.schools.offboarding.archive', $school['id']) }}">
                @csrf
                <p>{{ __('Archiving signs the school\'s people out for good and schedules deletion for :date.', ['date' => $o['delete_on']]) }}</p>
                <button class="saas-btn saas-btn--danger" type="submit">{{ __('Archive the school') }}</button>
            </form>
        @else
            <p>{{ __('The school can be archived once it has received its export.') }}</p>
        @endif
    </div>

    <h2>{{ __('4. Deletion') }}</h2>
    <div class="saas-card">
        <p>{{ __('Deletion needs two approvals by different people, the second by an Owner. Each re-checks that the export was delivered and is intact, and that no legal hold is on. The approvals delete nothing: after the second one, a cooling-off period of :hours hours starts, and the erasure can begin once it is over and the deletion date has come. Until then a legal hold or a cancellation stops it.', ['hours' => $coolingOffHours]) }}</p>
        <ul>
            <li>{{ $o['deletion_requested_at'] ? __('Asked for :at by :name.', ['at' => $o['deletion_requested_at'], 'name' => $o['deletion_requested_by']]) : __('Not asked for yet.') }}</li>
            <li>{{ $o['deletion_approved_at'] ? __('Approved :at by :name.', ['at' => $o['deletion_approved_at'], 'name' => $o['deletion_approved_by']]) : __('Not approved yet.') }}</li>
            @if ($o['erasure_not_before'])<li>{{ __('Erasure can start from :at.', ['at' => $o['erasure_not_before']]) }}</li>@endif
        </ul>
        @if ($o['state'] === SchoolOffboarding::ARCHIVED && ! $o['legal_hold'])
            <div class="saas-actions">
                @if ($manage)
                    <form class="saas-inline" method="POST" action="{{ route('saas.schools.offboarding.dry-run', $school['id']) }}">@csrf<button class="saas-btn saas-btn--quiet" type="submit">{{ __('Run the deletion dry run') }}</button></form>
                @endif
                @if ($manage && ! $o['deletion_requested_at'])
                    <form class="saas-inline" method="POST" action="{{ route('saas.schools.offboarding.request', $school['id']) }}">@csrf<button class="saas-btn" type="submit">{{ __('Ask for deletion (first approval)') }}</button></form>
                @endif
                @if ($approve && $o['deletion_requested_at'] && ! $o['deletion_approved_at'] && $o['deletion_requested_by_id'] !== auth()->id())
                    <form class="saas-inline" method="POST" action="{{ route('saas.schools.offboarding.approve', $school['id']) }}">@csrf<button class="saas-btn saas-btn--danger" type="submit">{{ __('Approve deletion (second approval)') }}</button></form>
                @endif
            </div>
        @endif

        @if ($o['dry_run'] && ! empty($o['dry_run']['protected']))
            <p>{{ __('History the database keeps immutable (money, ledger and similar records) is never deleted: :rows rows in :tables tables, and the rows they refer to stay with them.', ['rows' => array_sum($o['dry_run']['protected']), 'tables' => count($o['dry_run']['protected'])]) }}</p>
        @endif
        @if ($o['dry_run'])
            <h3>{{ __('Deletion dry run, :at by :name', ['at' => $o['dry_run_at'], 'name' => $o['dry_run_by'] ?? __('System')]) }}</h3>
            <p>{{ __(':total rows in :tables of :checked tables would be deleted. Rows deleted: :deleted.', ['total' => $o['dry_run']['delete_total'], 'tables' => count($o['dry_run']['delete']), 'checked' => $o['dry_run']['tables_checked'], 'deleted' => $o['dry_run']['deleted']]) }}</p>
            <p>{{ __('People: :only accounts only this school knows would go; :shared also belong to another school and would keep their account.', ['only' => $o['dry_run']['users']['only_this_school'], 'shared' => $o['dry_run']['users']['also_other_schools']]) }}</p>
            <div class="saas-table-wrap" role="region" aria-label="{{ __('Rows that would be deleted') }}" tabindex="0">
                <table class="saas-table">
                    <caption>{{ __('Rows that would be deleted, per table') }}</caption>
                    <thead><tr><th scope="col">{{ __('Table') }}</th><th scope="col" class="num">{{ __('Rows') }}</th></tr></thead>
                    <tbody>
                    @foreach ($o['dry_run']['delete'] as $table => $rows)
                        <tr><th scope="row"><code>{{ $table }}</code></th><td class="num">{{ $rows }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="saas-table-wrap" role="region" aria-label="{{ __('Kept') }}" tabindex="0">
                <table class="saas-table">
                    <caption>{{ __('Kept, and why') }}</caption>
                    <thead><tr><th scope="col">{{ __('Table') }}</th><th scope="col" class="num">{{ __('Rows') }}</th><th scope="col">{{ __('Why') }}</th></tr></thead>
                    <tbody>
                    @foreach ($o['dry_run']['kept'] as $table => $kept)
                        <tr><th scope="row"><code>{{ $table }}</code></th><td class="num">{{ $kept['rows'] }}</td><td>{{ $kept['reason'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <h2>{{ __('5. Erasure') }}</h2>
    <div class="saas-card">
        @if ($o['state'] === SchoolOffboarding::ERASED && $o['certificate'])
            <p>{{ __('Erased :at. Certificate :reference.', ['at' => $o['erased_at'], 'reference' => $o['certificate']['reference']]) }}</p>
            <p>{{ __(':rows rows erased in :tables tables; :accounts accounts only this school knew were erased. Kept: the audit log and Navigate Ed\'s own records of the school.', ['rows' => $o['certificate']['certificate']['erased']['rows_total'], 'tables' => $o['certificate']['certificate']['erased']['tables'], 'accounts' => $o['certificate']['certificate']['erased']['accounts']]) }}</p>
            @if ($o['certificate']['certificate']['held'] !== [])
                <p>{{ __('Held back: :rows rows of history the database keeps immutable, or that this history refers to.', ['rows' => array_sum(array_column($o['certificate']['certificate']['held'], 'rows'))]) }}</p>
            @endif
            <p>{{ __('SHA-256') }}: <code>{{ $o['certificate']['certificate_sha256'] }}</code></p>
            <p><a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.offboarding.certificate', $school['id']) }}">{{ __('Download the certificate') }}</a></p>
        @elseif ($o['state'] === SchoolOffboarding::ERASING)
            <p>{{ __('Erasing since :at (:name). Rows erased so far: :rows. Accounts erased: :accounts. Last progress :heartbeat.', ['at' => $o['erasure_started_at'], 'name' => $o['erasure_started_by'] ?? __('System'), 'rows' => array_sum((array) ($o['erasure_progress']['rows'] ?? [])), 'accounts' => (int) ($o['erasure_progress']['accounts_erased'] ?? 0), 'heartbeat' => $o['erasure_heartbeat_at'] ?? '—']) }}</p>
            @if ($o['erasure_failure_code'])<p role="alert">{{ __('Stopped: :code. Nothing outside the school was touched. Fix the cause, then resume.', ['code' => $o['erasure_failure_code']]) }}</p>@endif
            <p>{{ __('An erasure that has started cannot be stopped, only completed. If it stalls, it is resumed automatically from where it stopped.') }}</p>
            @if ($manage)
                <form method="POST" action="{{ route('saas.schools.offboarding.erase.resume', $school['id']) }}">@csrf<button class="saas-btn" type="submit">{{ __('Resume the erasure') }}</button></form>
            @endif
        @elseif ($o['state'] === SchoolOffboarding::ARCHIVED && $o['deletion_approved_at'] && ! $o['legal_hold'])
            @if (! $o['erasure_due'])
                <p>{{ __('Cooling-off: the erasure can start from :at.', ['at' => $o['erasure_not_before']]) }}</p>
            @elseif ($manage)
                <form method="POST" action="{{ route('saas.schools.offboarding.erase', $school['id']) }}" aria-label="{{ __('Start the erasure') }}">
                    @csrf
                    <p>{{ __('This erases the school\'s data for good. It cannot be stopped once it starts. The school\'s export, both approvals and that nothing outside the school refers to its rows are checked again first.') }}</p>
                    <div class="saas-field"><label for="erase-confirm">{{ __('Type :reference to confirm', ['reference' => $o['reference']]) }}</label><input id="erase-confirm" name="confirm" required maxlength="16" autocomplete="off"></div>
                    <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Erase the school\'s data') }}</button></div>
                </form>
            @else
                <p>{{ __('The erasure can start now.') }}</p>
            @endif
        @else
            <p>{{ __('The erasure can start once deletion is approved twice and the cooling-off period is over.') }}</p>
        @endif
    </div>

    @if (! in_array($o['state'], [SchoolOffboarding::ERASING, SchoolOffboarding::ERASED], true))
    <h2>{{ __('Legal hold') }}</h2>
    <div class="saas-card">
        @if ($o['legal_hold'])
            <p>{{ __('On since :at (:name): :reason', ['at' => $o['legal_hold_at'], 'name' => $o['legal_hold_by'], 'reason' => $o['legal_hold_reason']]) }}</p>
            @if ($approve)
                <form method="POST" action="{{ route('saas.schools.offboarding.hold.lift', $school['id']) }}">@csrf @method('DELETE')<button class="saas-btn" type="submit">{{ __('Lift the legal hold') }}</button></form>
            @endif
        @elseif ($manage)
            <form method="POST" action="{{ route('saas.schools.offboarding.hold', $school['id']) }}" aria-label="{{ __('Place a legal hold') }}">
                @csrf
                <p>{{ __('A legal hold stops deletion and cancels any approvals given so far. Only an Owner can lift it.') }}</p>
                <div class="saas-field"><label for="hold-reason">{{ __('Reason for the hold') }}</label><textarea id="hold-reason" name="reason" required minlength="10" maxlength="500" rows="2"></textarea></div>
                <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Place a legal hold') }}</button></div>
            </form>
        @else
            <p>{{ __('No legal hold.') }}</p>
        @endif
    </div>

    @endif

    @if ($manage && ! in_array($o['state'], [SchoolOffboarding::ERASING, SchoolOffboarding::ERASED], true))
    <h2>{{ __('Cancel') }}</h2>
    <form method="POST" action="{{ route('saas.schools.offboarding.cancel', $school['id']) }}" class="saas-card" aria-label="{{ __('Cancel this offboarding') }}">
        @csrf
        <p>{{ __('Cancelling discards the export copy. An archived school stays archived until its status is restored.') }}</p>
        <div class="saas-field"><label for="cancel-reason">{{ __('Reason for cancelling') }}</label><textarea id="cancel-reason" name="reason" required minlength="10" maxlength="500" rows="2"></textarea></div>
        <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Cancel this offboarding') }}</button></div>
    </form>
    @endif
@endif

@if ($cancelled !== [])
<h2>{{ __('Cancelled offboardings') }}</h2>
<ul class="saas-card">@foreach ($cancelled as $old)<li>{{ $old['reference'] }} · {{ $old['cancelled_at'] }}</li>@endforeach</ul>
@endif
@endsection
