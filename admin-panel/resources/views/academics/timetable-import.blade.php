<details id="import" class="m3-card m3-card--outlined page-section" @if($import) open @endif><summary>{{ __('Import timetable') }}</summary><div class="m3-card__content">
    <h2>{{ __('Import from a spreadsheet or timetabling export') }}</h2><p>{{ __('Paste a UTF-8 CSV or tab-separated export. Match its columns and school values, then review clashes before saving. Up to 300 lessons per batch.') }}</p>
    @php $fields = ['teaching_group_id' => __('Teaching group'), 'class_section_id' => __('Homeroom'), 'teacher_id' => __('Teacher'), 'room_id' => __('Room'), 'bell_period_id' => __('Period'), 'weekday' => __('Day'), 'starts_on' => __('Starts on'), 'ends_on' => __('Ends on'), 'week_pattern' => __('Week pattern'), 'occurs_on' => __('Specific date')]; @endphp
    @if (!$import)
        <form method="POST" action="{{ route('academic-timetable.import-map') }}" aria-label="{{ __('Read timetable sheet') }}">@csrf @include('academics.timetable-context')
            <x-page.field name="csv" type="textarea" :label="__('Timetable sheet')" rows="8" maxlength="524288" required />
            <button type="submit" class="m3-button m3-button--filled">{{ __('Read sheet columns') }}</button>
        </form>
    @elseif ($import['stage'] === 'columns')
        <h3>{{ __('Match sheet columns') }}</h3><p>{{ __('Choose either a teaching group or homeroom column. Teacher, room, period and day must be supplied by the sheet.') }}</p>
        <form method="POST" action="{{ route('academic-timetable.import-map') }}" aria-label="{{ __('Match timetable columns') }}">@csrf @include('academics.timetable-context')
            <input type="hidden" name="stage" value="values"><textarea name="csv" hidden>{{ $import['csv'] }}</textarea>
            <div class="timetable-fields">@foreach ($fields as $field => $label)
                <x-page.field :name="'columns['.$field.']'" type="select" :label="$label" :options="array_combine($import['sheet']['headers'], $import['sheet']['headers'])" :value="in_array($field, $import['sheet']['headers'], true) ? $field : null" :placeholder="__('Use default or leave blank')" />
            @endforeach</div>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Match school values') }}</button>
        </form>
    @else
        <h3>{{ __('Match school values') }}</h3><p>{{ __('Choose the exact school record for each exported label. Names are never guessed.') }}</p>
        <form method="POST" action="{{ route('academic-timetable.import-preview') }}" aria-label="{{ __('Preview timetable import') }}" data-import-value-form>@csrf @include('academics.timetable-context')
            <textarea name="csv" hidden>{{ $import['csv'] }}</textarea><input type="hidden" name="values_json" data-import-values>
            @foreach ($import['columns'] as $field => $column)<input type="hidden" name="columns[{{ $field }}]" value="{{ $column }}">@endforeach
            <div class="timetable-import-map">
                @foreach ($import['columns'] as $field => $column)
                    @if (isset($choices[$field]))
                        <fieldset class="timetable-period"><legend>{{ $fields[$field] ?? $field }} · {{ $column }}</legend>
                            @foreach ($import['sheet']['values'][$column] as $index => $source)
                                <label class="m3-text-field m3-text-field--select" for="map-{{ $field }}-{{ $index }}">
                                    <select id="map-{{ $field }}-{{ $index }}" class="m3-text-field__input" required data-map-field="{{ $field }}" data-map-source="{{ $source }}">
                                        <option value="">{{ __('Choose') }}</option>@foreach ($choices[$field] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                                    </select><span class="m3-text-field__label">{{ $source }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif
                @endforeach
            </div>
            <h4>{{ __('Defaults for columns left blank') }}</h4>
            <div class="timetable-fields">
                <x-page.field name="defaults[starts_on]" type="date" :label="__('Default start date')" :value="max($weekOn, $year->start_date)" required />
                <x-page.field name="defaults[ends_on]" type="date" :label="__('Default end date')" :value="min(\Carbon\CarbonImmutable::parse($weekOn)->addDays(6)->toDateString(), $year->end_date)" required />
                <x-page.field name="defaults[week_pattern]" type="select" :label="__('Default weeks')" value="all" :options="['all' => __('All weeks'), 'odd' => __('Odd weeks'), 'even' => __('Even weeks')]" required />
            </div>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Preview import clashes') }}</button>
            <noscript><p>{{ __('Enable JavaScript to match timetable export values.') }}</p></noscript>
        </form>
    @endif
</div></details>
