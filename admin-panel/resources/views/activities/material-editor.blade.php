<x-m3.card :title="__('Group material')">
@php($action='material-save')
@php($saved=$edit==='material' ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<form method="POST" action="{{ route('activity-groups.material-save',['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="title" :label="__('Material title')" :value="$draft['title'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="textarea" name="markdown" :label="__('Material Markdown')" :value="$draft['markdown'] ?? ''" required maxlength="20000" rows="8" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="published" :label="__('Material visibility')" :options="['0'=>__('Private draft'),'1'=>__('Published to group members')]" :value="(int) ($draft['published'] ?? false)" />
<x-m3.button type="submit">{{ __('Save material') }}</x-m3.button>
</form>
</x-m3.card>
