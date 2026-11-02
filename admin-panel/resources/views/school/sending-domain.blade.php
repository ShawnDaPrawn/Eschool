@extends('layouts.master')
@section('title', __('Email from your domain'))
@section('content')
@php
    $statusLabels = [
        'pending' => __('Waiting for the DNS records'),
        'verified' => __('Verified'),
        'failed' => __('Not verified'),
    ];
    $problems = [
        'dkim_failed' => __('The DKIM records were not found within 72 hours, so the check stopped. Disconnect the domain and connect it again to get new records.'),
        'dkim_temporary_failure' => __('Your DNS could not be read at the last check. It is checked again automatically.'),
        'ses_unreachable' => __('The mail service could not be reached at the last check. It is checked again automatically.'),
        'identity_missing' => __('The mail service no longer has this domain. Disconnect it and connect it again.'),
        'host_not_found' => __('The records were not found yet. DNS changes can take up to 72 hours to show.'),
        'type_not_found' => __('The records were not found yet. DNS changes can take up to 72 hours to show.'),
    ];
    $purposes = [
        'dkim' => __('DKIM: proves the mail comes from your school'),
        'mail_from' => __('MAIL FROM: where bounces go'),
        'spf' => __('SPF for the MAIL FROM domain'),
        'dmarc' => __('DMARC: recommended; keep your own record if you already have one'),
    ];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Email from your domain')" :subtitle="__('Send your school\'s notices from your own domain, so families see your school\'s name and address. Password and invitation emails still come from NavigateEd.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @unless ($enabled)
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="sending-off"><div class="m3-card__content">
            <h2 id="sending-off">{{ __('Not switched on yet') }}</h2>
            <p>{{ __('Sending from your own domain is not switched on yet. Your notices are sent from the NavigateEd address.') }}</p>
        </div></section>
    @endunless

    @if ($domain === null)
        @if ($enabled)
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="sending-connect"><div class="m3-card__content">
            <h2 id="sending-connect">{{ __('Connect your domain') }}</h2>
            <p>{{ __('Use a domain your school controls, for example school.co.za or mail.school.co.za. After you connect it, you add a few DNS records at the company that manages your domain.') }}</p>
            <form method="POST" action="{{ route('sending-domain.store') }}" aria-label="{{ __('Connect your domain') }}">
                @csrf
                <x-page.field name="domain" id="sending-domain" :label="__('Domain')" :help="__('The domain only, without www, https:// or an email address.')" maxlength="253" autocomplete="off" spellcheck="false" required />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Connect domain') }}</button>
            </form>
        </div></section>
        @endif
    @else
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="sending-status"><div class="m3-card__content">
            <h2 id="sending-status">{{ $domain->domain }}</h2>
            <dl>
                <dt>{{ __('Status') }}</dt>
                <dd>{{ $statusLabels[$domain->status] ?? $domain->status }}</dd>
                <dt>{{ __('Notices are sent from') }}</dt>
                <dd>
                    @if ($sender && $enabled)
                        {{ $schoolName }} &lt;{{ $sender }}&gt;
                    @else
                        {{ __('The NavigateEd address, until the domain is verified.') }}
                    @endif
                </dd>
                <dt>{{ __('MAIL FROM records') }}</dt>
                <dd>{{ $domain->mail_from_status === 'SUCCESS' ? __('Found') : __('Not found yet. Mail is still delivered meanwhile.') }}</dd>
                <dt>{{ __('Last checked') }}</dt>
                <dd>{{ $domain->last_checked_at ? $domain->last_checked_at->timezone(config('app.timezone'))->format('Y-m-d H:i') : __('Not yet') }}</dd>
            </dl>
            @if ($domain->last_error)
                <p role="alert">{{ $problems[$domain->last_error] ?? __('The last check found a problem with the DNS records. Check that each record below is exactly as shown.') }}</p>
            @endif
            @if ($enabled)
                <form method="POST" action="{{ route('sending-domain.check', $domain->id) }}">
                    @csrf
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Check now') }}</button>
                </form>
            @endif
            <p>{{ __('The domain is also checked automatically every few minutes until it is verified, then once a day.') }}</p>
        </div></section>

        <section class="page-section" aria-labelledby="sending-records">
            <h2 id="sending-records">{{ __('DNS records to add') }}</h2>
            <p>{{ __('Add these records at the company that manages your domain\'s DNS. Copy each name and value exactly. Some DNS providers add your domain to the name for you; if so, leave it off the end of the name.') }}</p>
            @if ($domain->dkim_signing_hosted_zone === null)
                <p role="status">{{ __('The DKIM DNS target is not available yet. Check the domain again before adding DKIM records.') }}</p>
            @endif
            <div class="page-table__scroll" role="region" aria-label="{{ __('DNS records to add') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('DNS records to add') }}</caption>
                <thead><tr><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Value') }}</th><th scope="col">{{ __('What it is for') }}</th></tr></thead>
                <tbody>
                @foreach ($records as $record)
                    <tr>
                        <th scope="row">{{ $record['type'] }}</th>
                        <td><code>{{ $record['name'] }}</code></td>
                        <td><code>{{ $record['value'] }}</code></td>
                        <td>{{ $purposes[$record['purpose']] ?? '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>

        @if ($enabled)
        <section class="page-section" aria-labelledby="sending-disconnect">
            <h2 id="sending-disconnect">{{ __('Disconnect the domain') }}</h2>
            <p>{{ __('Notices go back to the NavigateEd address straight away. You can then remove the DNS records.') }}</p>
            <form method="POST" action="{{ route('sending-domain.destroy', $domain->id) }}">
                @csrf
                @method('DELETE')
                <button class="m3-button m3-button--outlined" type="submit" data-confirm="{{ __('Notices will be sent from the NavigateEd address instead of :domain.', ['domain' => $domain->domain]) }}"
                    data-confirm-title="{{ __('Disconnect the domain') }}" data-confirm-label="{{ __('Disconnect') }}">{{ __('Disconnect') }}</button>
            </form>
        </section>
        @endif
    @endif
</div>
@endsection
