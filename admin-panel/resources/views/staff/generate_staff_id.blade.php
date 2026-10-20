@extends('layouts.master')

@section('title')
    {{ __('generate') . ' ' . __('id') . ' ' . __('card') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('generate') . ' ' . __('id') . ' ' . __('card') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('staff') }}
                        </h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-2">
                                <label>{{ __('type') }}</label>
                                {{-- The picker (#table_list) reads this value via data-* on change; see generate_staff_id.js --}}
                                <select name="staff_id_card_type" id="staff_id_card_type" class="form-control">
                                    <option value="" @selected($defaultType === '')>{{ __('all') }}</option>
                                    <option value="teacher" @selected($defaultType === 'teacher')>{{ __('teacher') }}</option>
                                    <option value="staff" @selected($defaultType === 'staff')>{{ __('staff') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-unique-id="id" data-url="{{ route('generate_staff_id.list') }}"
                                    data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200 , 500]" data-search="true"
                                    data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                    data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                    data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                    data-query-params="staffIdCardQueryParams"
                                    data-response-handler="staffIdCardResponseHandler" data-check-on-init="true"
                                    data-escape="true">
                                    <thead>
                                        <tr>
                                            <th data-field="state" data-checkbox="true"></th>
                                            <th data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                            <th data-field="name" data-sortable="false" data-escape="false" data-width="260"
                                                data-min-width="240" data-formatter="userInfoFormatter">{{ __('name') }}
                                            </th>
                                            <th data-field="designation" data-sortable="false">
                                                {{ __('designation') }}</th>
                                            <th data-field="mobile" data-sortable="false">{{ __('mobile') }}</th>
                                            <th data-field="gender" data-sortable="false" data-escape="false"
                                                data-formatter="genderFormatter">{{ __('gender') }}</th>
                                            <th data-field="dob" data-sortable="false">{{ __('dob') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="form-group col-12">
                            <form action="{{ route('generate_staff_id.card') }}" target="_blank" method="post">
                                @csrf
                                <textarea id="user_id" name="user_id" style="display: none"></textarea>
                                <input type="submit" id="generate_id_btn" class="btn btn-theme mt-4"
                                    value="{{ __('generate') }}" disabled>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script
        src="{{ asset('assets/js/generate_staff_id.js') }}?v={{ filemtime(public_path('assets/js/generate_staff_id.js')) }}">
    </script>
@endsection
