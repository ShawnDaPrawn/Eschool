@extends('groups.layout')
@section('title', __('Your school groups'))
@section('content')
<x-page.header :title="__('Your school groups')" :subtitle="__('Choose a group to see its figures.')" :breadcrumbs="false" />
<x-m3.card>
    <ul class="group-list">
        @foreach ($groups as $id => $name)
            <li><a href="{{ route('group.show', $id) }}" class="m3-button m3-button--outlined">{{ $name }}</a></li>
        @endforeach
    </ul>
</x-m3.card>
@endsection
