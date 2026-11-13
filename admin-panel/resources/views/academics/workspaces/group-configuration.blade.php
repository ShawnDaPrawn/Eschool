    @if ($manage)
        <h2>{{ __('Class grade mappings') }}</h2>
        <p>{{ __('Map grades explicitly before assigning learners. Existing dated group history protects used mappings.') }}</p>
        @foreach ($classes as $class)
            <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                <h3>{{ $class->name }}</h3>
                @can('class-edit')<form method="POST" action="{{ route('academic-structure.map', $class->id) }}">@csrf @method('PUT')
                    <label class="m3-text-field m3-text-field--select" for="class-grade-{{ $class->id }}"><select class="m3-text-field__input" name="grade_level_id" id="class-grade-{{ $class->id }}" aria-label="{{ __('Grade for class') }} {{ $class->name }}">
                        <option value="">{{ __('Not mapped') }}</option>@foreach ($grades as $id => $name)<option value="{{ $id }}" @selected((string) $id === (string) $class->grade_level_id)>{{ $name }}</option>@endforeach
                    </select><span class="m3-text-field__label">{{ __('Grade for class') }} {{ $class->name }}</span></label>
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save grade mapping') }}</button>
                </form>@else<p>{{ $class->gradeLevel?->name ?? __('Not mapped') }}</p>@endcan
            </div></section>
        @endforeach
    @endif
