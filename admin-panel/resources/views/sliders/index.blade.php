@extends('layouts.master')

@section('title')
    {{ __('sliders') }}
@endsection

@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('manage') . ' ' . __('sliders')" />
        @php($websiteView = request('view') === 'create' ? 'create' : 'list')
        <x-panel-view-nav :current="$websiteView" :items="[
            'list'=>['label'=>'App and website banners','href'=>request()->url()],
            'create'=>['label'=>'Create banner','href'=>request()->url().'?view=create'],
        ]" />
        <div class="panel-form-grid">
            @if($websiteView === 'create')
            <div class="   search-container">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <h2 class="card-title">
                            {{ __('create') . ' ' . __('sliders') }}
                        </h2>
                        <div class="">
                            <form class="pt-3 sliders-create-form" id="create-form" action="{{ route('sliders.store') }}" method="POST" novalidate="novalidate" enctype="multipart/form-data">
                                <div class="panel-form-grid">
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('slider_type') }} <span class="text-danger">*</span></label>
                                            <div class="input-group ">
                                                <select name="type" id="type" class="form-control">
                                                    <option value="">{{__('Please Select')}}</option>
                                                    <option value="1">{{ __('app')}}</option>
                                                    <option value="2">{{ __('web')}}</option>
                                                    <option value="3">{{ __('both')}}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('image') }} <span class="text-danger">*</span></label>
                                            <input type="file" name="image" class="file-upload-default" accept="image/*"/>
                                            <div class="input-group ">
                                                <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                                <span class="input-group-append">
                                                    <button class="file-upload-browse m3-button m3-button--filled" type="button">{{ __('upload') }}</button>
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
                            {{ __('list') . ' ' . __('sliders') }}
                        </h2>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('sliders.show', [1]) }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                               data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="true"
                               data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc"
                               data-maintain-selected="true" data-export-types='["txt","excel"]'
                               data-export-options='{ "fileName": "slider-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}' data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                <th scope="col" data-field="type" data-sortable="true" data-formatter="sliderTypeFormatter">{{ __('type') }}</th>
                                <th scope="col" data-field="image" data-sortable="false" data-formatter="imageFormatter">{{ __('image') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-escape="false" data-field="operate" data-sortable="false" data-events="sliderEvents">{{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('sliders') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 sliders-edit-form" id="edit-form" action="{{ url('sliders') }}" novalidate="novalidate">
                            <div class="modal-body">
                                <input type="hidden" name="edit_id" id="edit_id" value=""/>
                                <div class="form-group">
                                    <label>{{ __('slider_type') }} <span class="text-danger">*</span></label>
                                    <div class="input-group ">
                                        <select name="type" id="edit_type" class="form-control">
                                            <option value="">{{__('Please Select')}}</option>
                                            <option value="1">{{ __('app')}}</option>
                                            <option value="2">{{ __('web')}}</option>
                                            <option value="3">{{ __('both')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('image') }} <span class="text-danger">*</span></label>
                                    <input type="file" name="image" class="file-upload-default edit_image" accept="image/*"/>
                                    <div class="input-group ">
                                        <input type="text" class="form-control edit_image" value="" placeholder="{{__('image')}}" />
                                        <span class="input-group-append">
                                        <button class="file-upload-browse m3-button m3-button--filled" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="w-100 text-center">
                                        <img src="" id="edit_slider_image" class="w-100">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="m3-button m3-button--outlined" data-dismiss="modal">{{__('close')}}</button>
                                <input class="m3-button m3-button--filled" type="submit" value={{ __('edit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
