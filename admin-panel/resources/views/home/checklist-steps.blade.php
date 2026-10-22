<ol class="m3-list m3-list--divided setup-checklist-steps">
    @foreach ($checklist['items'] as $item)
        <li class="m3-list__item setup-step {{ $item['done'] ? 'is-done' : '' }}" data-checklist-item="{{ $item['key'] }}"
            data-done="{{ $item['done'] ? 'true' : 'false' }}">
            <span class="m3-list__leading setup-step-icon">
                <x-icon :name="$item['done'] ? 'circle-check' : 'circle-dashed'" :size="24" />
                <span class="m3-visually-hidden">{{ $item['done'] ? __('checklist_step_done') : __('checklist_step_to_do') }}</span>
            </span>
            <div class="m3-list__content setup-step-text">
                <span class="m3-list__headline setup-step-title">{{ $item['title'] }}</span>
                <span class="m3-list__supporting setup-step-detail">{{ $item['done'] ? $item['detail'] : $item['hint'] }}</span>
                @unless ($item['done'])
                    <span class="m3-list__supporting setup-step-detail setup-step-now">{{ $item['detail'] }}</span>
                @endunless
            </div>
            @if ($item['can_act'])
                <a class="m3-button {{ $item['done'] ? 'm3-button--outlined' : 'm3-button--filled' }} m3-button--small setup-step-action" href="{{ $item['url'] }}">
                    {{ $item['done'] ? __('checklist_review') : __('checklist_do_it') }}
                    <span class="m3-visually-hidden">: {{ $item['title'] }}</span>
                </a>
            @endif
        </li>
    @endforeach
</ol>
