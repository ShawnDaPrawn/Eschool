@if ($errors->any())<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if (session('success'))<p role="status">{{ session('success') }}</p>@endif
