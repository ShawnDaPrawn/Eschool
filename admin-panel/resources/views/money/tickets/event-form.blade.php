<form method="post" action="{{ route('ticket-events.event') }}" aria-label="{{ __('Retain event sale and admission windows') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$draft['revision']??$event?->revision??0])
@if($event)<input type="hidden" name="id" value="{{ $event->id }}">@endif
<fieldset @disabled($readOnly || $event?->status==='cancelled')><legend>{{ __('Event sale and admission windows') }}</legend>
<x-page.field id="ticket-event-name" :restore-old-input="false" name="name" :label="__('Event name')" :value="$draft['name']??$event?->name" maxlength="191" required />
<x-page.field id="ticket-event-description" :restore-old-input="false" type="textarea" name="description" :label="__('Event description')" :value="$draft['description']??$event?->description" maxlength="4000" />
<x-page.field id="ticket-event-status" :restore-old-input="false" type="select" name="status" :label="__('Event status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'paused'=>__('Paused')]" :value="$draft['status']??$event?->status??'draft'" required />
@foreach(['sales_from'=>'Ticket sales start','sales_until'=>'Ticket sales end','starts_at'=>'Admission opens','ends_at'=>'Admission closes'] as $field => $label)
<x-page.field :id="'ticket-event-'.$field" :restore-old-input="false" type="datetime-local" :name="$field" :label="__($label)" :value="$draft[$field]??$event?->getAttribute($field)?->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d\TH:i')" required />
@endforeach
<p>{{ __('Enter event windows in the selected school timezone.') }}</p>
<x-page.field id="ticket-event-reason" :restore-old-input="false" type="textarea" name="private_reason" :value="$draft['private_reason']??''" :label="__('Private reason for this event revision')" minlength="10" maxlength="2000" required />
<x-m3.button variant="text" :href="route('ticket-events.index',$event?['event_id'=>$event->id]:[])">{{ __('Cancel') }}</x-m3.button>
<x-m3.button type="submit">{{ __('Retain event sale and admission windows') }}</x-m3.button></fieldset></form>
