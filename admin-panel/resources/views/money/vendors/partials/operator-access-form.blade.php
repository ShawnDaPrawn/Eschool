<form method="post" action="{{ route('vendor-operators.bind') }}" aria-label="{{ __('Change vendor access').' '.$binding->id }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="vendor_id" value="{{ $binding->vendor_id }}"><input type="hidden" name="user_id" value="{{ $binding->user_id }}"><input type="hidden" name="revision" value="{{ $action?->revision??0 }}"><input type="hidden" name="status" value="{{ $action?->status==='active'?'revoked':'active' }}">
<fieldset @disabled(!$canWrite && $action?->status!=='active')><legend>{{ __('Review this counter assignment') }}</legend>
<x-page.field :id="'vendor-operator-change-'.$binding->id" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private access change reason')" required minlength="3" maxlength="2000" />
<label><input type="checkbox" name="confirmed_operator_identity" value="1" required> {{ __('I checked this operator and this specific vendor counter.') }}</label>
<x-m3.button type="submit">{{ $action?->status==='active'?__('Revoke this counter access'):__('Restore this counter access') }}</x-m3.button>
</fieldset></form>
