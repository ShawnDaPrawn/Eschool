@php($isRetry=$failedAction===$action && !$conflict)
<input type="hidden" name="operation_id" value="{{ $isRetry ? ($restored['operation_id'] ?? \Illuminate\Support\Str::uuid()) : \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="revision" value="{{ $isRetry ? ($restored['revision'] ?? $saved['revision'] ?? 0) : ($saved['revision'] ?? 0) }}">
@if($conflict && $failedAction===$action)<p>{{ __('Current saved revision') }}: {{ $saved['revision'] ?? 0 }}</p><x-m3.checkbox name="reviewed_conflict" value="1" :label="__('I reviewed the saved record and want to save my changes against this revision.')" required />@endif
