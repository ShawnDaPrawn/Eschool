@extends('layouts.master')

@section('title')
{{ __('fees') }} {{__('configration')}}
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('/assets/css/fees-config.css') }}?v={{ filemtime(public_path('assets/css/fees-config.css')) }}">
@endsection

@section('content')
<div class="content-wrapper fees-config-page">
    <div class="page-header">
        <div>
            <h3 class="page-title">{{__('manage')}} {{ __('fees') }} {{__('configration')}}</h3>
            <p class="page-subtitle">{{ __('currency_symbol') }}, {{ __('fees') }} {{ __('installment') }} &amp; {{ __('payment_gateways') }}</p>
        </div>
    </div>

    <form id="create-fees-config-form" class="fees-config" action="{{ route('fees.config.udpate') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- ── General configuration ─────────────────────────────── --}}
        <div class="card fees-card">
            <div class="card-body">
                <div class="fees-section-head">
                    <span class="fees-section-head__icon"><x-icon name="settings" :size="20" /></span>
                    <div class="fees-section-head__text">
                        <h4 class="fees-section-head__title">{{ __('general') }} {{ __('configration') }}</h4>
                        <p class="fees-section-head__subtitle">{{ __('currency_code') }}, {{ __('fees') }} {{ __('installment') }} &amp; {{ __('due') }} {{ __('charges') }}</p>
                    </div>
                </div>

                {{-- Inputs row --}}
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>{{ __('currency_code') }} <span class="text-danger">*</span></label>
                        <input name="currency_code" value="{{ isset($settings['currency_code']) ? $settings['currency_code'] : '' }}" type="text" placeholder="{{ __('currency_code') }}" class="form-control" />
                        <span class="field-hint">{{__('eg_currency_code_zar')}}</span>
                    </div>

                    <div class="form-group col-md-4">
                        <label>{{ __('currency_symbol') }} <span class="text-danger">*</span></label>
                        <input name="currency_symbol" value="{{ isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '' }}" type="text" placeholder="{{ __('currency_symbol') }}" class="form-control" />
                        <span class="field-hint">{{__('eg_currency_symbol_r')}}</span>
                    </div>

                    <div class="form-group col-md-4">
                        <label>{{ __('default') }} {{ __('due') }} {{ __('charges') }} (%) <span class="text-danger">*</span></label>
                        <input name="default_due_charges" value="{{ isset($settings['default_due_charges']) ? $settings['default_due_charges'] : '5' }}" type="number" min="0" max="100" placeholder="{{ __('default') }} {{ __('due') }} {{ __('charges') }}" class="form-control" />
                        <span class="field-hint">{{ __('percentage') }} {{ __('of') }} {{ __('total') }} {{ __('amount') }}</span>
                    </div>
                </div>

                {{-- Toggles row --}}
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>{{ __('enable') }} {{ __('fees') }} {{ __('installment') }} <span class="text-danger">*</span></label>
                        <div class="fees-toggle-group">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="enable_fees_installment" class="fees_installment_toggle" value="1" {{ isset($settings['enable_fees_installment']) && $settings['enable_fees_installment'] == '1' ? 'checked' : '' }}>
                                    {{ __('enable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="enable_fees_installment" class="fees_installment_toggle" value="0" {{ isset($settings['enable_fees_installment']) && $settings['enable_fees_installment'] == '0' ? 'checked' : '' }}>
                                    {{ __('disable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label>{{__('compulsory_fee_payment_mode') }} <span class="text-danger">*</span></label>
                        <div class="fees-toggle-group">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="compulsory_fee_payment_mode" class="online_payment_toggle" value="1" {{ isset($settings['compulsory_fee_payment_mode']) && $settings['compulsory_fee_payment_mode'] == '1' ? 'checked' : '' }}>
                                    {{ __('enable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="compulsory_fee_payment_mode" class="online_payment_toggle" value="0" {{ isset($settings['compulsory_fee_payment_mode']) && $settings['compulsory_fee_payment_mode'] == '0' ? 'checked' : '' }}>
                                    {{ __('disable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label>{{ __('is_student_can_pay_fees') }} <span class="text-danger">*</span></label>
                        <div class="fees-toggle-group">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="is_student_can_pay_fees" class="online_payment_toggle" value="1" {{ isset($settings['is_student_can_pay_fees']) && $settings['is_student_can_pay_fees'] == '1' ? 'checked' : '' }}>
                                    {{ __('enable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input type="radio" name="is_student_can_pay_fees" class="online_payment_toggle" value="0" {{ isset($settings['is_student_can_pay_fees']) && $settings['is_student_can_pay_fees'] == '0' ? 'checked' : '' }}>
                                    {{ __('disable') }}
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Payment gateways ──────────────────────────────────── --}}
        <div class="card fees-card">
            <div class="card-body">
                <div class="fees-section-head">
                    <span class="fees-section-head__icon"><x-icon name="wallet" :size="20" /></span>
                    <div class="fees-section-head__text">
                        <h4 class="fees-section-head__title">{{ __('payment_gateways') }}</h4>
                        <p class="fees-section-head__subtitle">{{ __('stripe') }}</p>
                    </div>
                </div>

                <ul class="nav fees-gateway-tabs" id="paymentGatewayTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-stripe" data-toggle="tab" href="#pane-stripe" role="tab" aria-controls="pane-stripe" aria-selected="true">
                            <x-icon name="credit-card" :size="16" /> {{ __('stripe') }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content fees-gateway-panel" id="paymentGatewayTabContent">

                    {{-- Stripe --}}
                    <div class="tab-pane fade show active" id="pane-stripe" role="tabpanel" aria-labelledby="tab-stripe">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                <div class="fees-toggle-group">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input type="radio" name="stripe_status" class="online_payment_toggle" value="1" {{ isset($settings['stripe_status']) && $settings['stripe_status'] == '1' ? 'checked' : '' }}>
                                            {{ __('enable') }}
                                            <i class="input-helper"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input type="radio" name="stripe_status" class="online_payment_toggle" value="0" {{ !isset($settings['stripe_status']) || $settings['stripe_status'] == '0' ? 'checked' : '' }}>
                                            {{ __('disable') }}
                                            <i class="input-helper"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('charge_currency') }}</label>
                                <input name="stripe_currency_code" value="{{ isset($settings['stripe_currency_code']) ? $settings['stripe_currency_code'] : '' }}" type="text" placeholder="{{ __('charge_currency') }}" class="form-control" />
                                <span class="field-hint">{{ __('charge_currency_hint') }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('stripe_publishable_key') }}</label>
                                <input name="stripe_publishable_key" value="{{ !config('app.demo_mode') ? ($settings['stripe_publishable_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_publishable_key') }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('stripe_secret_key') }}</label>
                                <input name="stripe_secret_key" value="{{ !config('app.demo_mode') ? ($settings['stripe_secret_key'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_secret_key') }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('stripe_webhook_secret') }}</label>
                                <input name="stripe_webhook_secret" value="{{ !config('app.demo_mode') ? ($settings['stripe_webhook_secret'] ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('stripe_webhook_secret') }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('stripe') }} {{ __('webhook_url') }}</label>
                                <input name="stripe_webhook_url" value="{{ !config('app.demo_mode') ? ($domain.'/webhook/stripe' ?? '') : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}" type="text" placeholder="{{ __('webhook_url') }}" class="form-control" readonly />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="fees-config-actions">
            <button type="submit" class="btn btn-theme">{{ __('Submit') }}</button>
        </div>
    </form>
</div>
@endsection
