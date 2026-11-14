<nav aria-label="{{ __('Consultation navigation') }}"><a href="{{ route('consultations-panel.index') }}">{{ __('All consultations') }}</a></nav>
@if($messages)<div role="alert" class="m3-card"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(app(\App\Services\Panel\SessionYearContext::class)->isReadOnly() || $year !== $access['session_year_id'])
    <p role="status">{{ __('This view is read only. Return to the current school year to make changes, or ask a school administrator about module access.') }}</p>
@elseif(!$access['writable'])
    <p role="status">{{ __('Consultation changes are unavailable under the school’s current module settings.') }}</p>
@elseif(isset($configuration) && $configuration && $configuration['status'] !== 'draft')
    <p role="status">{{ __('Published consultation settings are frozen. Review the saved configuration and use the available record actions below.') }}</p>
@endif
