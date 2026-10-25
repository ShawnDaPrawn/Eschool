<nav aria-label="{{ __('Consultation navigation') }}"><a href="{{ route('consultations-panel.index') }}">{{ __('All consultations') }}</a></nav>
@if($messages)<div role="alert" class="m3-card"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p role="status">{{ __('This view is read only. Return to the current school year to make changes, or ask a school administrator about module access.') }}</p>@endif
