@extends('layouts.master')
@section('title', __('Imports by Navigate Ed'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Imports by Navigate Ed')" :subtitle="__('Files Navigate Ed prepared to import for the school. Nothing is imported until the leader it was sent to checks the rows and approves it. Every step is listed.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif

    @if ($imports === [])
        <p class="page-section">{{ __('Navigate Ed has not prepared any imports for the school.') }}</p>
    @else
        <div class="page-table__scroll page-section" role="region" aria-label="{{ __('Imports by Navigate Ed') }}" tabindex="0"><table class="page-table__table">
            <caption class="m3-visually-hidden">{{ __('Imports by Navigate Ed') }}</caption>
            <thead><tr>
                <th scope="col">{{ __('Sent') }}</th>
                <th scope="col">{{ __('Import') }}</th>
                <th scope="col">{{ __('Rows') }}</th>
                <th scope="col">{{ __('To approve') }}</th>
                <th scope="col">{{ __('Status') }}</th>
            </tr></thead>
            <tbody>
            @foreach ($imports as $import)
                <tr>
                    <td><a href="{{ route('hq-imports.show', $import['id']) }}">{{ $import['sent_at'] }}</a></td>
                    <th scope="row">{{ $import['kind_label'] }}</th>
                    <td>{{ $import['rows'] }}</td>
                    <td>{{ $import['approver'] ?? __('Unknown') }}</td>
                    <td>{{ $import['status_label'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</div>
@endsection
