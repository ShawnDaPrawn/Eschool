@php($formAction=$group ? 'save' : 'create')
@php($draft=$failedAction===$formAction ? $restored : $group)
<x-m3.card :title="$group['name'] ?? __('New activity group')">
<form method="POST" action="{{ $group ? route('activity-groups.save',['group'=>$group['id']]) : route('activity-groups.create') }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['action'=>$formAction,'saved'=>$group])
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="name" :label="__('Group name')" :value="$draft['name'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="parent_id" :label="__('Parent group')" :options="[''=>__('No parent group')]+$groups->where('active',true)->where('id','!=',$group['id'] ?? 0)->pluck('name','id')->all()" :value="$draft['parent_id'] ?? ''" />
@foreach(['discipline'=>'Discipline','season'=>'Season'] as $key=>$label)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" :name="$key" :label="__($label)" :value="$draft[$key] ?? ''" required maxlength="100" />@endforeach
@include('activities.dates')
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="gender" :label="__('Gender eligibility')" :options="['any'=>__('Any'),'male'=>__('Male'),'female'=>__('Female')]" :value="$draft['gender'] ?? 'any'" />
@foreach(['minimum_grade'=>'Minimum grade (R = 0)','maximum_grade'=>'Maximum grade (R = 0)','minimum_age'=>'Minimum age','maximum_age'=>'Maximum age'] as $key=>$label)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="number" :name="$key" :label="__($label)" :value="$draft[$key] ?? ''" min="0" :max="str_contains($key,'grade') ? 12 : 25" />@endforeach
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="active" :label="__('Group availability')" :options="['1'=>__('Active'),'0'=>__('Archived')]" :value="(int) ($draft['active'] ?? true)" />
<x-m3.button type="submit">{{ __('Save group') }}</x-m3.button>
</form>
@if($conflict && $failedAction===$formAction)<h3>{{ __('Current saved values') }}</h3><dl>@foreach($group ?? [] as $key=>$value)<dt>{{ __($key) }}</dt><dd>{{ is_bool($value) ? ($value ? __('Yes') : __('No')) : $value }}</dd>@endforeach</dl>@endif
</x-m3.card>
