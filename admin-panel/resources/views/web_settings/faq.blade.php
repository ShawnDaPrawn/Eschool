@extends('layouts.master')

@section('title')
    {{ __('question') }}
@endsection
@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('manage') . ' ' . __('faqs') . ' ' . __('question')" />
        @php($websiteView = request('view') === 'create' ? 'create' : 'list')
        <x-panel-view-nav :current="$websiteView" :items="[
            'list'=>['label'=>'Website FAQs','href'=>request()->url()],
            'create'=>['label'=>'Create FAQ','href'=>request()->url().'?view=create'],
        ]" />
        <div class="panel-form-grid">
            @if($websiteView === 'create')
            <div class="   search-container">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <h2 class="card-title">
                            {{ __('create') . ' ' . __('faqs') . ' ' . __('question') }}
                        </h2>
                        <div class="">
                            <form class="pt-3 create-form faq-create-form" id="create-form" action="{{route('faq.store')}}" method="POST" novalidate="novalidate" enctype="multipart/form-data">
                                <div class="panel-form-grid">
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('question') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('question', null, ['required', 'placeholder' => __('question'), 'class' => 'form-control', 'id' => 'question', 'rows' => 1]) !!}
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('answer') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('answer', null, ['required', 'placeholder' => __('answer'), 'class' => 'form-control', 'id' => 'answer', 'rows' => 4]) !!}
                                        </div>
                                    </div>
                                    <div class="">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', '1',false,['id' => 'active']) !!}
                                                    {{ __('active') }}
                                                <i class="input-helper"></i>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', '0',false,['id' => 'inactive']) !!}
                                                    {{ __('inactive') }}
                                                <i class="input-helper"></i>
                                                </label>
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
                            {{ __('list'). ' ' . __('faqs') . ' ' . __('question') }}
                        </h2>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{route('faq.show',1)}}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                               data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="true"
                               data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-escape="true"
                               data-maintain-selected="true" data-export-types='["txt","excel"]'
                               data-export-options='{ "fileName": "photo-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'>
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                <th scope="col" data-field="question" data-sortable="true">{{ __('question') }}</th>
                                <th scope="col" data-field="answer" data-sortable="true">{{ __('answer') }}</th>
                                <th scope="col" data-field="status" data-sortable="true" data-formatter="shiftStatusFormatter">{{ __('status') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-escape="false" data-field="operate" data-sortable="false" data-events="actionEvents">{{ __('action') }}</th>
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
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit'). ' ' . __('faqs') . ' ' . __('question') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 edit-faq" id="edit-video" method="POST" action="{{route('faq.update',1)}}" novalidate="novalidate">
                            <div class="modal-body">
                                <div class="panel-form-grid">
                                    <div class="">
                                        <input type="hidden" name="edit_id" id="edit_id"/>
                                        <div class="form-group">
                                            <label>{{ __('question') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('question', null, ['required', 'placeholder' => __('question'), 'class' => 'form-control', 'id' => 'edit_question','rows' => 2]) !!}
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="form-group">
                                            <label>{{ __('answer') }} <span class="text-danger">*</span></label>
                                            {!! Form::textarea('answer', null, ['required', 'placeholder' => __('answer'), 'class' => 'form-control', 'id' => 'edit_answer' ,'rows' => 4]) !!}
                                        </div>
                                    </div>
                                    <div class="">
                                        <label>{{ __('status') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', '1',false,['id' => 'edit_status_active']) !!}
                                                    {{ __('active') }}
                                                <i class="input-helper"></i>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', '0',false,['id' => 'edit_status_inactive']) !!}
                                                    {{ __('inactive') }}
                                                <i class="input-helper"></i>
                                                </label>
                                            </div>
                                        </div>
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
@section('script')
<script type="text/javascript">

    window.actionEvents = {
        'click .edit-data': function (e, value, row, index) {

            $('#edit_id').val(row.id);
            $('#edit_question').val(row.question);
            $('#edit_answer').val(row.answer);
            if(row.status == 1)
            {
                    $('#edit_status_inactive').attr('checked',false);
                    $('#edit_status_active').attr('checked',true);
            }
            else
            {
                    $('#edit_status_active').attr('checked',false);
                    $('#edit_status_inactive').attr('checked',true);
            }

        }
    };
    </script>

    <script type="text/javascript">
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
@endsection
