@extends('saas.layout')

@section('title', __('School record').' · '.$school['name'])

@section('content')
@php
    $options = fn (array $values, string $prefix) => collect($values)->mapWithKeys(fn ($value) => [$value => __($prefix.$value)])->all();
    $money = fn (int $cents) => 'R '.number_format(intdiv($cents, 100)).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
@endphp
<h1>{{ $school['name'] }}</h1>
<p class="saas-lead">
    <span class="saas-badge">{{ __('stage_'.$account['stage']) }}</span>
    · {{ __('Navigate Ed\'s record of this customer: its contacts, contracts and history. No learner, parent or staff record is shown here.') }}
</p>
<nav aria-label="{{ __('School record sections') }}" class="saas-actions">
    <a href="#profile">{{ __('Profile and stage') }}</a><a href="#contacts">{{ __('Contacts') }}</a><a href="#contracts">{{ __('Contracts') }}</a><a href="#timeline">{{ __('Timeline') }}</a>
</nav>

<h2 id="profile">{{ __('Profile and stage') }}</h2>
<form method="POST" action="{{ route('saas.schools.account.save', $school['id']) }}" class="saas-card" aria-label="{{ __('School profile and stage') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="revision" value="{{ $account['revision'] }}">
    <div class="saas-grid">
        @foreach (['stage' => [\App\Services\Platform\Saas\PlatformSchoolAccounts::STAGES, 'stage_', __('Stage')], 'school_type' => [\App\Services\Platform\Saas\PlatformSchoolAccounts::TYPES, 'school_type_', __('School type')],
            'phase' => [\App\Services\Platform\Saas\PlatformSchoolAccounts::PHASES, 'school_phase_', __('Phase')], 'curriculum' => [\App\Services\Platform\Saas\PlatformSchoolAccounts::CURRICULA, 'curriculum_', __('Curriculum')],
            'province' => [\App\Services\Platform\Saas\PlatformSchoolAccounts::PROVINCES, 'province_', __('Province')]] as $field => [$values, $prefix, $label])
            <div class="saas-field">
                <label for="account-{{ $field }}">{{ $label }}</label>
                <select id="account-{{ $field }}" name="{{ $field }}" @required($field === 'stage')>
                    @if ($field !== 'stage')<option value="">{{ __('Not recorded') }}</option>@endif
                    @foreach ($options($values, $prefix) as $value => $text)
                        <option value="{{ $value }}" @selected(old($field, $account[$field] ?? null) === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="saas-field">
            <label for="account-district">{{ __('Education district') }}</label>
            <input id="account-district" name="district" maxlength="100" value="{{ old('district', $account['district'] ?? '') }}">
        </div>
        <div class="saas-field">
            <label for="account-emis">{{ __('EMIS number') }}</label>
            <input id="account-emis" name="emis_number" maxlength="32" value="{{ old('emis_number', $account['emis_number'] ?? '') }}">
        </div>
        <div class="saas-field">
            <label for="account-learners">{{ __('Expected learners') }}</label>
            <input id="account-learners" name="expected_learners" type="number" min="1" max="20000" value="{{ old('expected_learners', $account['expected_learners'] ?? '') }}">
        </div>
        <div class="saas-field">
            <label for="account-address">{{ __('Physical address') }}</label>
            <textarea id="account-address" name="address" maxlength="500" rows="2">{{ old('address', $account['address'] ?? '') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save school record') }}</button></div>
</form>

<h2 id="contacts">{{ __('Contacts') }}</h2>
<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('The people Navigate Ed works with at this school') }}</caption>
        <thead><tr><th scope="col">{{ __('Role') }}</th><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Email') }}</th><th scope="col">{{ __('Phone') }}</th><th scope="col">{{ __('Remove') }}</th></tr></thead>
        <tbody>
        @forelse ($contacts as $contact)
            <tr>
                <td>{{ __('contact_'.$contact['role']) }}</td><th scope="row">{{ $contact['name'] }}</th><td>{{ $contact['email'] }}</td><td>{{ $contact['phone'] }}</td>
                <td>
                    <form class="saas-inline" method="POST" action="{{ route('saas.schools.contacts.destroy', [$school['id'], $contact['id']]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="saas-btn saas-btn--quiet" type="submit" aria-label="{{ __('Remove') }} {{ $contact['name'] }}">{{ __('Remove') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('No contacts recorded yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <form method="POST" action="{{ route('saas.schools.contacts.store', $school['id']) }}" aria-label="{{ __('Add a contact') }}">
        @csrf
        <h3>{{ __('Add a contact') }}</h3>
        <div class="saas-grid">
            <div class="saas-field">
                <label for="contact-role">{{ __('Role') }}</label>
                <select id="contact-role" name="role" required>
                    @foreach ($options(\App\Services\Platform\Saas\PlatformSchoolAccounts::CONTACT_ROLES, 'contact_') as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                </select>
            </div>
            <div class="saas-field"><label for="contact-name">{{ __('Name') }}</label><input id="contact-name" name="name" maxlength="191" required></div>
            <div class="saas-field"><label for="contact-email">{{ __('Email') }}</label><input id="contact-email" name="email" type="email" maxlength="191"></div>
            <div class="saas-field"><label for="contact-phone">{{ __('Phone') }}</label><input id="contact-phone" name="phone" type="tel" maxlength="32"></div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add contact') }}</button></div>
    </form>
</div>

<h2 id="contracts">{{ __('Contracts') }}</h2>
<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('Contracts are kept as history: a change is recorded as a new contract') }}</caption>
        <thead><tr><th scope="col">{{ __('From') }}</th><th scope="col">{{ __('Until') }}</th><th scope="col" class="num">{{ __('Learners') }}</th><th scope="col" class="num">{{ __('Per learner per month') }}</th><th scope="col">{{ __('Operator agreement') }}</th><th scope="col">{{ __('Signed by') }}</th><th scope="col">{{ __('Renewal reminder') }}</th><th scope="col">{{ __('Data processing addendum') }}</th><th scope="col">{{ __('Notes') }}</th></tr></thead>
        <tbody>
        @forelse ($contracts as $contract)
            <tr>
                <th scope="row">{{ $contract['starts_on'] }}</th><td>{{ $contract['ends_on'] ?? __('Open-ended') }}</td>
                <td class="num">{{ $contract['contracted_learners'] }}</td><td class="num">{{ $money($contract['price_per_learner_cents']) }}</td>
                <td>{{ $contract['operator_agreement_version'] }} · {{ $contract['operator_agreement_signed_on'] }}</td><td>{{ $contract['signatory'] }}</td>
                <td>{{ $contract['renewal_on'] ?? '—' }}</td><td>{{ $contract['data_processing_signed_on'] === null ? __('Not signed') : __('Signed :date', ['date' => $contract['data_processing_signed_on']]) }}</td><td>{{ $contract['notes'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="9">{{ __('No contract recorded yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <form method="POST" action="{{ route('saas.schools.contracts.store', $school['id']) }}" aria-label="{{ __('Record a contract') }}">
        @csrf
        <h3>{{ __('Record a contract') }}</h3>
        <div class="saas-grid">
            <div class="saas-field"><label for="contract-starts">{{ __('Starts on') }}</label><input id="contract-starts" name="starts_on" type="date" required value="{{ old('starts_on') }}"></div>
            <div class="saas-field"><label for="contract-ends">{{ __('Ends on (optional)') }}</label><input id="contract-ends" name="ends_on" type="date" value="{{ old('ends_on') }}"></div>
            <div class="saas-field"><label for="contract-renewal">{{ __('Renewal reminder on (optional)') }}</label><input id="contract-renewal" name="renewal_on" type="date" value="{{ old('renewal_on') }}"></div>
            <div class="saas-field"><label for="contract-learners">{{ __('Contracted learners') }}</label><input id="contract-learners" name="contracted_learners" type="number" min="1" max="20000" required value="{{ old('contracted_learners') }}"></div>
            <div class="saas-field"><label for="contract-price">{{ __('Price per learner per month, before VAT (R)') }}</label><input id="contract-price" name="price_per_learner" inputmode="decimal" pattern="\d{1,5}(\.\d{1,2})?" required value="{{ old('price_per_learner') }}"></div>
            <div class="saas-field"><label for="contract-signatory">{{ __('Signed by') }}</label><input id="contract-signatory" name="signatory" maxlength="191" required value="{{ old('signatory') }}"></div>
            <div class="saas-field"><label for="contract-agreement">{{ __('Operator agreement version') }}</label><input id="contract-agreement" name="operator_agreement_version" maxlength="32" required value="{{ old('operator_agreement_version') }}"></div>
            <div class="saas-field"><label for="contract-agreement-signed">{{ __('Operator agreement signed on') }}</label><input id="contract-agreement-signed" name="operator_agreement_signed_on" type="date" required value="{{ old('operator_agreement_signed_on') }}"></div>
            <div class="saas-field"><label for="contract-dpa">{{ __('Data processing addendum signed on (optional)') }}</label><input id="contract-dpa" name="data_processing_signed_on" type="date" value="{{ old('data_processing_signed_on') }}"></div>
            <div class="saas-field"><label for="contract-notes">{{ __('Notes (optional)') }}</label><textarea id="contract-notes" name="notes" maxlength="500" rows="2">{{ old('notes') }}</textarea></div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record contract') }}</button></div>
    </form>
</div>

<h2 id="timeline">{{ __('Timeline') }}</h2>
<div class="saas-card">
    <form method="POST" action="{{ route('saas.schools.notes.store', $school['id']) }}" aria-label="{{ __('Add a note') }}">
        @csrf
        <div class="saas-field">
            <label for="note-body">{{ __('Note') }}</label>
            <textarea id="note-body" name="body" minlength="3" maxlength="1000" rows="2" required></textarea>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add note') }}</button></div>
    </form>
    <ol class="saas-timeline">
        @forelse ($events as $event)
            <li><time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->timezone('Africa/Johannesburg')->format('j M Y H:i') }}</time> · {{ $event['actor'] ?? __('System') }}: {{ $event['body'] }}</li>
        @empty
            <li>{{ __('Nothing recorded yet.') }}</li>
        @endforelse
    </ol>
</div>
<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.show', $school['id']) }}">{{ __('Back to the school') }}</a></p>
@endsection
