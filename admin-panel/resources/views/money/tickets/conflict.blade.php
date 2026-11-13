@extends('layouts.master')
@section('title', __('Admission configuration changed'))
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Admission configuration changed')" :subtitle="__('Review the saved revision before making another change. No attempted changes were applied.')" />
<div class="page-table__scroll" role="region" aria-label="{{ __('Compare admission configuration') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Compare admission configuration') }}</caption><thead><tr><th scope="col">{{ __('Field') }}</th><th scope="col">{{ __('Your attempt') }}</th><th scope="col">{{ __('Saved version') }}</th></tr></thead><tbody>
@foreach($fields as $field=>$label)<tr><th scope="row">{{ __($label) }}</th><td>{{ $attempt[$field]??'—' }}</td><td>{{ $saved[$field]??'—' }}</td></tr>@endforeach
</tbody></table></div>
<x-m3.button :href="$returnUrl">{{ __('Review saved admission configuration') }}</x-m3.button>
</div>
@endsection
