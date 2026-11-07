@extends('layouts.master')
@section('title', __('SMS and WhatsApp'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('SMS and WhatsApp')" />
    <p>{{ __('Urgent notices and absence alerts can also go to guardians by SMS or WhatsApp. Each message is charged to the school at the price NavigateEd pays for it. Both are off until you switch them on here.') }}</p>
    <p>{{ __('Messages go to the mobile number on the guardian\'s parent record at this school. An SMS never contains a child\'s name or details: it says there is an urgent message and asks the guardian to open the NavigateEd app. Only a notice sent to the whole school includes its title and message.') }}</p>
    <p>{{ __('Staff can also get urgent notices and safeguarding alerts by SMS or WhatsApp, on the mobile number on their own account, if you switch that on below.') }}</p>
    <p>{{ __('A number is only texted after its owner has verified it in the NavigateEd app with a code sent to it by SMS. Numbers recorded before verification started are not texted until their owner verifies them. Each person can choose SMS, WhatsApp, both or neither for each notice; you can see that someone has chosen, but not change their choice.') }}</p>
    <p role="status">{{ trans_choice(':count person at this school has verified a number.|:count people at this school have verified a number.', $people['verified'], ['count' => $people['verified']]) }}</p>
    <p>{{ __('Emergency alerts and lockdowns go to guardians by every channel switched on here. For staff texts, choose the staff channels below. Number verification and each person’s own choices still apply.') }}</p>
    @if ($errors->any())<div role="alert">{{ $errors->first() }}</div>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if (! $configured['sms'])
        <p role="note">{{ __('NavigateEd has not finished setting up SMS yet. Your choices are saved, and messages start once it is ready.') }}</p>
    @endif
    @if (! $configured['whatsapp'])
        <p role="note">{{ __('NavigateEd has not finished setting up WhatsApp yet. Your choices are saved, and messages start once it is ready.') }}</p>
    @endif

    <form method="POST" action="{{ route('urgent-messaging.update') }}" class="m3 m3-card m3-card--outlined page-table">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision" value="{{ $setting['revision'] }}">
        <fieldset>
            <legend>{{ __('Channels') }}</legend>
            <input type="hidden" name="sms_enabled" value="0">
            <x-m3.switch name="sms_enabled" id="urgent-sms-enabled" :label="__('Send SMS')" :checked="$setting['sms_enabled']" />
            <input type="hidden" name="whatsapp_enabled" value="0">
            <x-m3.switch name="whatsapp_enabled" id="urgent-whatsapp-enabled" :label="__('Send WhatsApp messages')" :checked="$setting['whatsapp_enabled']" />
            @if (! $setting['whatsapp_consent_confirmed'])
                <x-m3.checkbox name="whatsapp_consent" id="urgent-whatsapp-consent" :label="__('Guardians have agreed to receive WhatsApp messages from the school.')" />
            @endif
        </fieldset>

        <div class="page-table__scroll" tabindex="0" aria-label="{{ __('Notices sent by SMS and WhatsApp') }}">
        <table class="page-table__table">
            <caption>{{ __('Notices sent by SMS and WhatsApp') }}</caption>
            <thead><tr><th scope="col">{{ __('Notice') }}</th><th scope="col">{{ __('What the message says') }}</th><th scope="col">{{ __('Guardians: SMS') }}</th><th scope="col">{{ __('Guardians: WhatsApp') }}</th><th scope="col">{{ __('Staff: SMS') }}</th><th scope="col">{{ __('Staff: WhatsApp') }}</th><th scope="col">{{ __('Own choices') }}</th></tr></thead>
            <tbody>
            @foreach ($types as $row)
                <tr>
                    <th scope="row">{{ __($row['label']) }}</th>
                    <td>{{ $row['content'] === 'broadcast_text' ? __('The title and message when sent to the whole school, otherwise only that there is an urgent message') : __('Only that there is an urgent message') }}</td>
                    <td>
                        @if ($row['guardian'])
                            <x-m3.checkbox name="sms_types[]" :value="$row['type']" :checked="$row['sms']" :label="__('SMS')" :aria-label="__('Send :notice by SMS', ['notice' => __($row['label'])])" />
                        @else
                            {{ __('Not for guardians') }}
                        @endif
                    </td>
                    <td>
                        @if (! $row['guardian'])
                            {{ __('Not for guardians') }}
                        @elseif ($row['whatsapp_available'])
                            <x-m3.checkbox name="whatsapp_types[]" :value="$row['type']" :checked="$row['whatsapp']" :label="__('WhatsApp')" :aria-label="__('Send :notice by WhatsApp', ['notice' => __($row['label'])])" />
                        @else
                            {{ __('Not available yet') }}
                        @endif
                    </td>
                    <td>
                        @if ($row['staff'])
                            <x-m3.checkbox name="staff_sms_types[]" :value="$row['type']" :checked="$row['staff_sms']" :label="__('SMS')" :aria-label="__('Send :notice to staff by SMS', ['notice' => __($row['label'])])" />
                        @else
                            {{ __('Not for staff') }}
                        @endif
                    </td>
                    <td>
                        @if (! $row['staff'])
                            {{ __('Not for staff') }}
                        @elseif ($row['whatsapp_available'])
                            <x-m3.checkbox name="staff_whatsapp_types[]" :value="$row['type']" :checked="$row['staff_whatsapp']" :label="__('WhatsApp')" :aria-label="__('Send :notice to staff by WhatsApp', ['notice' => __($row['label'])])" />
                        @else
                            {{ __('Not available yet') }}
                        @endif
                    </td>
                    <td>{{ trans_choice(':count person|:count people', $row['own_choices'], ['count' => $row['own_choices']]) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <button class="m3-button m3-button--filled" type="submit">{{ __('Save') }}</button>
    </form>
</div>
@endsection
