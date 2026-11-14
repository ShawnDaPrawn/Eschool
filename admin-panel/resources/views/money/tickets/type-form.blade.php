<form method="post" action="{{ route('ticket-events.type') }}" aria-label="{{ __('Retain admission capacity and transfer rule') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$draft['revision']??$type?->revision??0])
<input type="hidden" name="event_id" value="{{ $event->id }}">@if($type)<input type="hidden" name="id" value="{{ $type->id }}">@endif
<fieldset @disabled($readOnly || $event->status==='cancelled')><legend>{{ __('Admission capacity and transfer rule') }}</legend>
@if($type)
<input type="hidden" name="product_id" value="{{ $type->product_id }}"><input type="hidden" name="kind" value="{{ $type->kind }}">
<dl><dt>{{ __('Original admission product') }}</dt><dd>{{ $productOptions[$type->product_id] }}</dd><dt>{{ __('Original admission kind') }}</dt><dd>{{ __($type->kind==='named'?'Named learner admission':'General admission') }}</dd><dt>{{ __('Issued tickets') }}</dt><dd>{{ $issued }}</dd></dl>
@else
<x-page.field id="ticket-type-product" :restore-old-input="false" type="select" name="product_id" :label="__('Original admission product')" :options="$productOptions" :value="$draft['product_id']??$type?->product_id" required />
<x-page.field id="ticket-type-kind" :restore-old-input="false" type="select" name="kind" :label="__('Admission kind')" :options="[''=>__('Choose an explicit admission kind'),'general'=>__('General admission'),'named'=>__('Named learner admission')]" :value="$draft['kind']??$type?->kind??''" required />
@endif
<x-page.field id="ticket-type-capacity" :restore-old-input="false" type="number" name="capacity" :label="__('Explicit admission capacity')" :value="$draft['capacity']??$type?->capacity" :min="max(1,$issued)" max="1000000" required />
<x-page.field id="ticket-type-transfer" :restore-old-input="false" type="select" name="transfer_rule" :label="__('School ticket transfer rule')" :options="[''=>__('Choose an explicit transfer rule'),'none'=>__('Transfers are disabled'),'families'=>__('Current school families may receive'),'wallet_profiles'=>__('Current wallet profiles may receive')]" :value="$draft['transfer_rule']??$type?->transfer_rule??''" required />
<x-page.field id="ticket-type-status" :restore-old-input="false" type="select" name="status" :label="__('Admission type status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'paused'=>__('Paused')]" :value="$draft['status']??$type?->status??'draft'" required />
<x-page.field id="ticket-type-reason" :restore-old-input="false" type="textarea" name="private_reason" :value="$draft['private_reason']??''" :label="__('Private reason for this admission revision')" minlength="10" maxlength="2000" required />
<p>{{ __('Named tickets keep their original learner. A receiving parent must currently be authorised for that learner. Capacity cannot fall below issued tickets.') }}</p>
<x-m3.button variant="text" :href="route('ticket-events.index',['event_id'=>$event->id,'type_id'=>$type?->id])">{{ __('Cancel') }}</x-m3.button>
<x-m3.button type="submit">{{ __('Retain admission capacity and transfer rule') }}</x-m3.button></fieldset></form>
