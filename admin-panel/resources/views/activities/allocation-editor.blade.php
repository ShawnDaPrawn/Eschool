<x-m3.card :title="__('Learner and staff allocations')">
<p>{{ __('Dates are inclusive. Cancel a future or current allocation without deleting its history.') }}</p>
@foreach([$view==='staff-edit'?'staff':'membership'] as $kind)
@php($action=$kind.'-save')
@php($saved=$edit===$kind ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<h3>{{ $kind==='staff' ? __('Allocate staff') : __('Allocate learner') }}</h3>
<form method="POST" action="{{ route('activity-groups.'.$action,['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
@php($personField=$kind==='staff' ? 'user_id' : 'learner_id')
@php($personNames=$kind==='staff' ? $staffNames : $learnerNames)
@if($saved)<p>{{ $personNames[$saved[$personField]] ?? __('Former school member') }}</p><input type="hidden" name="{{ $personField }}" value="{{ $saved[$personField] }}">@else<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" :name="$personField" :label="$kind==='staff' ? __('Staff member') : __('Learner')" :options="[''=>__('Choose a person')]+$personNames" :value="$draft[$personField] ?? ''" required />@endif
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="role" :label="__('Group role')" :options="$kind==='staff' ? ['facilitator'=>__('Facilitator'),'admin'=>__('Group administrator')] : ['member'=>__('Member'),'captain'=>__('Captain')]" :value="$draft['role'] ?? ($kind==='staff' ? 'facilitator' : 'member')" />
@if($kind==='staff')<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="cascade" :label="__('Descendant groups')" :options="['0'=>__('This group only'),'1'=>__('Include descendant groups as administrator')]" :value="(int) ($draft['cascade'] ?? false)" />@endif
@include('activities.dates')
@if($saved)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="active" :label="__('Allocation availability')" :options="['1'=>__('Active'),'0'=>__('Cancelled')]" :value="(int) ($draft['active'] ?? true)" />@endif
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="reason" :label="__('Reason')" :value="$draft['reason'] ?? ''" required minlength="3" maxlength="2000" />
<x-m3.button type="submit">{{ __('Save allocation') }}</x-m3.button>
</form>
@if($kind==='membership' && $suggestions !== [])
<h3>{{ __('In this group last year') }}</h3>
<p>{{ __('These learners were in this group at the end of last year, are at the school and meet its rules now. Add the ones who continue.') }}</p>
<ul class="activity-suggestions">@foreach($suggestions as $learnerId => $learnerName)<li>
<form method="POST" action="{{ route('activity-groups.membership-save',['group'=>$group['id']]) }}" aria-label="{{ __('Add :name', ['name' => $learnerName]) }}">@csrf
<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="0">
<input type="hidden" name="learner_id" value="{{ $learnerId }}"><input type="hidden" name="role" value="member">
<input type="hidden" name="starts_on" value="{{ max($group['starts_on'], now()->toDateString()) }}"><input type="hidden" name="ends_on" value="{{ $group['ends_on'] }}">
<input type="hidden" name="reason" value="{{ __('In this group last year') }}">
{{ $learnerName }} <x-m3.button type="submit" variant="text">{{ __('Add as member') }}<span class="m3-visually-hidden">: {{ $learnerName }}</span></x-m3.button>
</form></li>@endforeach</ul>
@endif
<ul>@foreach($kind==='staff' ? $roster['staff'] : $roster['memberships'] as $row)<li><a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>$kind==='staff'?'staff-edit':'member-edit','edit'=>$kind,'record_id'=>$row['id']]) }}">{{ $kind==='staff' ? ($staffNames[$row['user_id']] ?? __('Former school member')) : $row['learner_name'] }} · {{ __($row['role']) }} · {{ $row['starts_on'] }}–{{ $row['ends_on'] }} @if(!$row['active'])({{ __('Cancelled') }})@endif</a></li>@endforeach</ul>
@endforeach
</x-m3.card>
