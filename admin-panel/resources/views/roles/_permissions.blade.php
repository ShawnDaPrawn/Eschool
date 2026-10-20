@php
    $selectedPermissions = $selectedPermissions ?? [];
    $totalPermissions = collect($permissionGroups)->sum(fn($g) => count($g['items']));
    $accordionId = 'permission-accordion-' . uniqid();
@endphp

{{-- Styles: public/assets/css/roles.css · Behaviour: public/assets/js/roles-permissions.js
     (both linked via @section('css') / @section('script') on the roles pages). --}}

<div class="row">
    <div class="col-12">
        <div class="permission-groups__toolbar">
            <label class="permission-groups__toolbar-label">
                <input type="checkbox" class="permission-groups__toolbar-input" data-permission-select-all>
                <span>{{ __('select_all') }}</span>
            </label>
            <div class="permission-groups__toolbar-actions">
                <button type="button" class="permission-groups__action-btn" data-permission-toggle-all
                    data-label-expand="{{ __('expand_all') }}" data-label-collapse="{{ __('collapse_all') }}"
                    aria-expanded="false">
                    <x-icon name="chevron-down" :size="14" class="permission-groups__action-icon" />
                    <span data-permission-toggle-label>{{ __('expand_all') }}</span>
                </button>
                <span class="permission-groups__counter">
                    <span data-permission-selected-count>0</span>
                    <span class="text-muted">/ {{ $totalPermissions }} {{ __('permission') }}</span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="permission-groups">
            <div class="accordion permission-accordion" id="{{ $accordionId }}">
                @foreach ($permissionGroups as $key => $group)
                    @php $itemId = $accordionId . '-item-' . $loop->index; @endphp
                    <div class="card" data-permission-group>
                        <div class="card-header" id="{{ $itemId }}-heading">
                            <label class="permission-group__header-label" onclick="event.stopPropagation();">
                                <input type="checkbox" class="permission-group__header-input"
                                    data-permission-group-toggle>
                                <span class="permission-group__title">{{ $group['title'] }}</span>
                            </label>
                            <div class="permission-group__header-meta">
                                <span class="permission-group__count">
                                    <span data-permission-group-count>0</span>/<span>{{ count($group['items']) }}</span>
                                </span>
                                <button class="card-header__toggle collapsed" type="button" data-toggle="collapse"
                                    data-target="#{{ $itemId }}-collapse" aria-expanded="false"
                                    aria-controls="{{ $itemId }}-collapse">
                                    <svg class="card-header__toggle-icon" viewBox="0 0 16 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div id="{{ $itemId }}-collapse" class="collapse"
                            aria-labelledby="{{ $itemId }}-heading">
                            <div class="card-body">
                                @foreach ($group['items'] as $item)
                                    <div class="permission-group__item">
                                        <label class="permission-group__item-label" title="{{ $item['name'] }}">
                                            <input type="checkbox" name="permission[]" value="{{ $item['id'] }}"
                                                class="permission-group__item-input" data-permission-child
                                                {{ in_array($item['id'], $selectedPermissions) ? 'checked' : '' }}>
                                            <span
                                                class="permission-group__item-text">{{ $item['action_label'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
