<ol class="setup-checklist-steps">
    @foreach ($checklist['items'] as $item)
        <li class="setup-step {{ $item['done'] ? 'is-done' : '' }}" data-checklist-item="{{ $item['key'] }}"
            data-done="{{ $item['done'] ? 'true' : 'false' }}">
            <span class="setup-step-icon">
                <x-icon :name="$item['done'] ? 'circle-check' : 'circle-dashed'" :size="22" />
                <span class="sr-only">{{ $item['done'] ? __('checklist_step_done') : __('checklist_step_to_do') }}</span>
            </span>
            <div class="setup-step-text">
                <span class="setup-step-title">{{ $item['title'] }}</span>
                <span class="setup-step-detail">{{ $item['done'] ? $item['detail'] : $item['hint'] }}</span>
                @unless ($item['done'])
                    <span class="setup-step-detail setup-step-now">{{ $item['detail'] }}</span>
                @endunless
            </div>
            @if ($item['can_act'])
                <a class="btn btn-sm {{ $item['done'] ? 'btn-secondary' : 'btn-theme' }} setup-step-action" href="{{ $item['url'] }}">
                    {{ $item['done'] ? __('checklist_review') : __('checklist_do_it') }}
                    <span class="sr-only">: {{ $item['title'] }}</span>
                </a>
            @endif
        </li>
    @endforeach
</ol>
