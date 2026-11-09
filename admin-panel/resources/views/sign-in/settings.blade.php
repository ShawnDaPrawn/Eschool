@extends('layouts.master')

@section('title', __('Google and Microsoft sign-in'))

@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Google and Microsoft sign-in')" :subtitle="__('Let your staff, and if you choose your older learners, sign in with the Google or Microsoft account your school gives them.')" />
    @if ($errors->any())
        <div role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <section class="page-section" aria-labelledby="sign-in-how">
        <h2 id="sign-in-how">{{ __('How it works') }}</h2>
        <p>{{ __('Nobody gets a new account this way. Each person first signs in with their password and links their school Google or Microsoft account on their linked accounts page. After that, the button on the sign-in page opens their own account, at this school only.') }}</p>
        <p>{{ __('Only accounts in the email domains or Microsoft tenants you list below can be linked or used. NavigateEd platform staff never sign in this way.') }}</p>
    </section>

    @foreach ($providers as $provider)
        <section class="page-section" aria-labelledby="provider-{{ $provider['provider'] }}">
            <h2 id="provider-{{ $provider['provider'] }}">{{ $provider['label'] }}</h2>
            @unless ($provider['configured'])
                <p>{{ __(':provider sign-in is not set up for NavigateEd yet. Ask NavigateEd to set it up first.', ['provider' => $provider['label']]) }}</p>
            @endunless
            <form method="POST" action="{{ route('sign-in-providers.update', ['provider' => $provider['provider']]) }}" aria-label="{{ __(':provider sign-in settings', ['provider' => $provider['label']]) }}">
                @csrf
                @method('PUT')
                <x-m3.checkbox name="enabled" value="1" :checked="$provider['enabled']" :label="__('Switch on :provider sign-in at this school', ['provider' => $provider['label']])" />
                <x-page.field name="allowed_domains" type="textarea" :id="'domains-'.$provider['provider']" :restore-old-input="false"
                    :value="implode(PHP_EOL, $provider['allowed_domains'])"
                    :label="__('Allowed email domains, one per line')"
                    :help="$provider['provider'] === 'google' ? __('Your school\'s Google Workspace domains, like school.org.za. At least one.') : __('Optional for Microsoft: when you list any, the email address must be in one of them.')" maxlength="2000" />
                @if ($provider['provider'] === 'microsoft')
                    <x-page.field name="allowed_tenants" type="textarea" :id="'tenants-'.$provider['provider']" :restore-old-input="false"
                        :value="implode(PHP_EOL, $provider['allowed_tenants'])"
                        :label="__('Allowed Microsoft tenant ids, one per line')"
                        :help="__('Your school\'s Microsoft Entra tenant id, from the Microsoft Entra admin centre. At least one.')" maxlength="2000" />
                @else
                    <x-m3.checkbox name="require_hosted_domain" value="1" :checked="$provider['require_hosted_domain']" :label="__('Only Google Workspace accounts that your school manages (recommended)')" />
                @endif

                <fieldset>
                    <legend>{{ __('Staff who may use it') }}</legend>
                    @php($access = $provider['staff_roles'] === null ? 'all' : ($provider['staff_roles'] === [] ? 'none' : 'some'))
                    <x-m3.radio name="staff_access" value="all" :checked="$access === 'all'" :label="__('Every staff role')" />
                    <x-m3.radio name="staff_access" value="some" :checked="$access === 'some'" :label="__('Only these roles')" />
                    @foreach ($staff_roles as $role)
                        <x-m3.checkbox name="staff_roles[]" :value="$role" :checked="in_array($role, (array) $provider['staff_roles'], true)" :label="$role" />
                    @endforeach
                    <x-m3.radio name="staff_access" value="none" :checked="$access === 'none'" :label="__('No staff')" />
                </fieldset>

                <fieldset>
                    <legend>{{ __('Learners') }}</legend>
                    <x-m3.checkbox name="learners_allowed" value="1" :checked="$provider['learners_allowed']" :label="__('Learners may use it too, in the age stages below')" />
                    @foreach ($stages as $stage => $stageLabel)
                        <x-m3.checkbox name="learner_stages[]" :value="$stage" :checked="in_array($stage, $provider['learner_stages'], true)" :label="__($stageLabel)" />
                    @endforeach
                    <p>{{ __('Learners in the early years never sign in themselves. Parents and guardians keep their own sign-in.') }}</p>
                </fieldset>

                {{-- Plain paragraph text, the page's own text colour: the legacy theme's pink <code> fails colour contrast. --}}
                <p>{{ __('Return address for the app registration') }}: <span translate="no">{{ $provider['redirect_uri'] }}</span></p>
                <p><button class="m3-button m3-button--filled" type="submit">{{ __('Save :provider settings', ['provider' => $provider['label']]) }}</button></p>
            </form>
        </section>
    @endforeach

    <section class="page-section" aria-labelledby="sign-in-links">
        <h2 id="sign-in-links">{{ __('Linked accounts at this school') }}</h2>
        @if ($links === [])
            <p>{{ __('Nobody has linked an account yet.') }}</p>
        @else
            <table class="page-table">
                <thead><tr><th scope="col">{{ __('Person') }}</th><th scope="col">{{ __('Account') }}</th><th scope="col">{{ __('Last used') }}</th><th scope="col">{{ __('Remove') }}</th></tr></thead>
                <tbody>
                    @foreach ($links as $link)
                        <tr>
                            <td>{{ $link['name'] }}</td>
                            <td>{{ $link['label'] }}: {{ $link['email'] }}</td>
                            <td>{{ $link['last_used_at'] ?? __('Not yet') }}</td>
                            <td>
                                <form method="POST" action="{{ route('sign-in-providers.links.destroy', ['link' => $link['id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="m3-button m3-button--text" type="submit">{{ __('Remove the link') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
