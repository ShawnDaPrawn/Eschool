<section class="m3-card m3-card--outlined page-section" data-programme-row>
    <div class="m3-card__content">
        <h3>{{ __('Programme item') }}</h3>
        @if($item)<input type="hidden" name="{{ $prefix }}[{{ $rowKey }}][id]" value="{{ $item->id }}">@endif
        <div class="panel-form-grid">
            <x-page.field :id="'programme-title-'.$prefix.'-'.$rowKey" :name="$prefix.'['.$rowKey.'][title]'" :label="__('Programme title')" :value="$item?->title" required maxlength="255" />
            <x-page.field :id="'programme-date-'.$prefix.'-'.$rowKey" :name="$prefix.'['.$rowKey.'][date]'" type="date" :label="__('Programme date')" :value="$item?->date" required />
            <div data-event-time-pair>
                <x-page.field :id="'programme-start-'.$prefix.'-'.$rowKey" :name="'programme_start_'.$prefix.'_'.$rowKey" type="time" :label="__('Start time')" :value="$item?->start_time" required data-time-start />
                <x-page.field :id="'programme-end-'.$prefix.'-'.$rowKey" :name="'programme_end_'.$prefix.'_'.$rowKey" type="time" :label="__('End time')" :value="$item?->end_time" required data-time-end />
                <input type="hidden" name="{{ $prefix }}[{{ $rowKey }}][timerange]" data-time-value value="{{ $item ? $item->start_time.' - '.$item->end_time : '' }}">
            </div>
            <x-page.field :id="'programme-description-'.$prefix.'-'.$rowKey" :name="$prefix.'['.$rowKey.'][description]'" type="textarea" :label="__('description')" :value="$item?->description" rows="3" />
        </div>
        @if(!$item || Auth::user()->can('event-delete'))
            <button type="button" class="m3-button m3-button--text" data-programme-remove @if($item) data-remove-url="{{ route('multiple.event.delete',$item->id) }}" @endif>{{ $item ? __('Delete saved programme item') : __('Remove programme item') }}</button>
        @endif
        <p role="status" data-programme-status hidden></p>
    </div>
</section>
