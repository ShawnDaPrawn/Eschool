<x-m3.card :title="__('Session details')">
@php($action='session-save')
@php($saved=$edit==='session' ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<form method="POST" action="{{ route('activity-groups.session-save',['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="date" name="on_date" :label="__('Session date')" :value="$draft['on_date'] ?? ''" required />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="time" name="starts_at" :label="__('Starts at (optional)')" :value="$draft['starts_at'] ?? ''" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="time" name="ends_at" :label="__('Ends at (optional)')" :value="$draft['ends_at'] ?? ''" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="name" :label="__('Session name')" :value="$draft['name'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="venue" :label="__('Venue')" :value="$draft['venue'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="status" :label="__('Session status')" :options="['scheduled'=>__('Scheduled'),'completed'=>__('Completed'),'cancelled'=>__('Cancelled')]" :value="$draft['status'] ?? 'scheduled'" />
<x-m3.button type="submit">{{ __('Save session') }}</x-m3.button>
</form>
</x-m3.card>
