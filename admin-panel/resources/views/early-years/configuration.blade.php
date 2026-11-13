<section class="m3-card m3-card--outlined page-section" aria-labelledby="early-years-areas">
    <div class="m3-card__content">
        <h2 id="early-years-areas">{{ __('Development areas') }}</h2>
        <p>{{ __('Each observation is tagged to one of these areas. Switched-off areas keep their observations but cannot be chosen for new ones.') }}</p>
        @if(in_array($workspaceView, ['configuration', 'areas'], true))
            @if($manage)<a class="m3-button m3-button--filled" href="{{ route('early-years.index', ['view' => 'area']) }}" data-write>{{ __('Add a development area') }}</a>@endif
            <div class="panel-record-list">
                @forelse($areas as $area)
                    <article class="panel-record-card">
                        <h3>{{ $area->name }}</h3>
                        <p>{{ $area->description }}</p>
                        <x-m3.chip :status="$area->active ? 'success' : 'neutral'">{{ $area->active ? __('Switched on') : __('switched off') }}</x-m3.chip>
                        @if($manage)<a class="m3-button m3-button--outlined" href="{{ route('early-years.index', ['view' => 'area', 'area_id' => $area->id]) }}">{{ __('Edit :name', ['name' => $area->name]) }}</a>@endif
                    </article>
                @empty
                    <x-page.empty-state :title="__('No development areas yet')" :message="__('Add a development area to organise observations.')" />
                @endforelse
            </div>
        @else
            <form method="POST" action="{{ $selectedArea ? route('early-years.areas.update', ['area' => $selectedArea->id]) : route('early-years.areas.store') }}" aria-label="{{ $selectedArea ? __('Development area').' '.$selectedArea->name : __('Add a development area') }}">
                @csrf
                @if($selectedArea)<input type="hidden" name="revision" value="{{ $selectedArea->revision }}">@endif
                <input type="hidden" name="active" value="0">
                <div class="panel-form-grid">
                    <x-page.field name="name" :label="__('Name')" :value="$selectedArea?->name" maxlength="80" required />
                    <x-page.field name="description" :label="__('Description')" :value="$selectedArea?->description" maxlength="255" />
                    <x-page.field name="position" type="number" :label="__('Order')" :value="$selectedArea?->position ?? 0" min="0" max="999" />
                </div>
                <x-m3.checkbox name="active" :label="__('Switched on')" value="1" :checked="old('active', $selectedArea?->active ?? true)" />
                <div class="panel-actions">
                    <x-m3.button type="submit">{{ $selectedArea ? __('Save area') : __('Add area') }}</x-m3.button>
                    <x-m3.button variant="text" :href="route('early-years.index', ['view' => 'configuration'])">{{ __('Cancel') }}</x-m3.button>
                </div>
            </form>
        @endif
    </div>
</section>
