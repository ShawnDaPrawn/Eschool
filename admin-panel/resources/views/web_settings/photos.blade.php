@extends('layouts.master')

@section('title')
    {{ __('photos') }}
@endsection
@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('manage') . ' ' . __('photos')" />
        @php($websiteView = request('view') === 'create' ? 'create' : 'list')
        <x-panel-view-nav :current="$websiteView" :items="[
            'list'=>['label'=>'Public photo collections','href'=>request()->url()],
            'create'=>['label'=>'Publish photo collection','href'=>request()->url().'?view=create'],
        ]" />
        <div class="panel-form-grid">
            @if($websiteView === 'create')
            <div class="   search-container">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <h2 class="card-title">
                            {{ __('create') . ' ' . __('photos') }}
                        </h2>
                        <div class="">
                            <form class="pt-3 photo-create-form" id="create-form" action="{{ route('photos.store') }}"
                                method="POST" novalidate="novalidate" enctype="multipart/form-data">
                                <div class="panel-form-grid">
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('name', null, [
                                                'required',
                                                'placeholder' => __('name'),
                                                'class' => 'form-control',
                                                'id' => 'name',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('thumbnail') }} <span class="text-danger">*</span></label>
                                            <input type="file" name="thumbnail" class="file-upload-default"
                                                accept="image/*" />
                                            <div class="input-group ">
                                                <input type="text" class="form-control file-upload-info"
                                                    placeholder="{{ __('thumbnail') }}" />
                                                <span class="input-group-append">
                                                    <button class="file-upload-browse m3-button m3-button--filled"
                                                        type="button">{{ __('upload') }}</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('image') }} <span class="text-danger">*</span></label>
                                            <input type="file" name="image[]" class="file-upload-default" multiple
                                                accept="image/*" />
                                            <div class="input-group ">
                                                <input type="text" class="form-control file-upload-info" disabled=""
                                                    placeholder="{{ __('image') }}" />
                                                <span class="input-group-append">
                                                    <button class="file-upload-browse m3-button m3-button--filled"
                                                        type="button">{{ __('upload') }}</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input class="m3-button m3-button--filled" id="create-btn" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($websiteView === 'list')
            <div class="   search-container">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <h2 class="card-title">
                            {{ __('list') . ' ' . __('photos') }}
                        </h2>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('photos.show', 1) }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="false" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                            data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                            data-sort-name="id" data-sort-order="desc" data-escape="true" data-maintain-selected="true"
                            data-export-types='["txt","excel"]'
                            data-export-options='{ "fileName": "photo-list-<?= date('d-m-y') ?>" ,"ignoreColumn":
                            ["operate"]}'>
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('title') }}</th>
                                    <th scope="col" data-field="thumbnail" data-sortable="true"
                                        data-formatter="thumbnailFormatter">{{ __('thumbnail') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">
                                        {{ __('updated_at') }}</th>
                                    <th scope="col" data-escape="false" data-field="operate" data-sortable="false">
                                        {{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
