@php($selectedPermissions = old('permission', $selectedPermissions ?? []))
<div data-permission-picker>
    <div class="panel-actions">
        <x-m3.checkbox data-permission-select-all :label="__('select_all')" />
        <button type="button" class="m3-button m3-button--text" data-permission-toggle-all aria-expanded="false" data-label-expand="{{ __('expand_all') }}" data-label-collapse="{{ __('collapse_all') }}"><span data-permission-toggle-label>{{ __('expand_all') }}</span></button>
        <span><span data-permission-selected-count>0</span> / {{ collect($permissionGroups)->sum(fn($group)=>count($group['items'])) }} {{ __('permission') }}</span>
    </div>
    <div class="permission-accordion panel-record-list">
    @foreach($permissionGroups as $group)
        <details class="m3-expansion" data-permission-group>
            <summary class="m3-expansion__header">{{ $group['title'] }} <span data-permission-group-count>0</span> / {{ count($group['items']) }}</summary>
            <div class="m3-expansion__body">
                <x-m3.checkbox data-permission-group-toggle :label="__('Select all permissions in :group', ['group'=>$group['title']])" />
                <div class="panel-form-grid">@foreach($group['items'] as $item)
                    <x-m3.checkbox name="permission[]" :value="$item['id']" :label="$item['action_label']" :checked="in_array($item['id'], $selectedPermissions)" data-permission-child />
                @endforeach</div>
            </div>
        </details>
    @endforeach
    </div>
</div>
