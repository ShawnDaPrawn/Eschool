@extends('layouts.master')

@section('title')
    {{ __('staff') . ' ' . __('management') }}
@endsection

@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="request('view') === 'create' ? (request('role') === 'Facilitator' ? __('Add facilitator') : __('Add admin staff')) : __('Admin staff')" :subtitle="__('School staff accounts, role assignments and contact details.')" :primary-label="request('view') === 'create' ? null : __('Add staff member')" :primary-href="route('staff.index', ['view' => 'create'])" primary-permission="staff-create" />
        <nav class="workspace-tabs" aria-label="{{ __('Staff workspace') }}"><a class="m3-button m3-button--text" href="{{ route('staff.index') }}" @if(request('view') !== 'create') aria-current="page" @endif>{{ __('Staff directory') }}</a>@can('staff-create')<a class="m3-button m3-button--text" href="{{ route('staff.index', ['view' => 'create']) }}" @if(request('view') === 'create') aria-current="page" @endif>{{ __('Add staff member') }}</a>@endcan</nav>
        <div class="row">
            @if(request('view') === 'create')
            @can('staff-create')
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('create') . ' ' . __('staff') }}
                            </h4>
                            <x-setup-needed :missing="['role' => $roles->isEmpty() ? url('roles/create') : null]" />
                            <form class="pt-3 staff-form" id="staff-form" enctype="multipart/form-data"
                                action="{{ route('staff.store') }}" method="POST" novalidate="novalidate">
                                @csrf
                                <p>{{ __('A staff invitation lets the account owner finish sign-in. Existing accounts must accept a school invitation before joining this school.') }}</p>
                                <div class="panel-form-grid">
                                    <x-page.field name="role_id" id="new-staff-role" type="select" :label="__('Role')" :placeholder="__('Choose a role')" :options="$roles->pluck('name', 'id')->all()" :value="$roles->firstWhere('name', request('role'))?->id" required />
                                    <x-page.field name="first_name" id="new-staff-first-name" :label="__('First name')" autocomplete="given-name" required />
                                    <x-page.field name="last_name" id="new-staff-last-name" :label="__('Last name')" autocomplete="family-name" required />
                                    <x-page.field name="email" id="new-staff-email" type="email" :label="__('Email address')" autocomplete="email" required />
                                    <x-page.field name="mobile" id="new-staff-mobile" type="tel" :label="__('Mobile number')" autocomplete="tel" class="mobile" required />
                                    <fieldset class="page-section"><legend>{{ __('Gender') }}</legend>
                                        <label class="page-choice"><input type="radio" name="gender" value="male" @checked(old('gender') === 'male') required> {{ __('male') }}</label>
                                        <label class="page-choice"><input type="radio" name="gender" value="female" @checked(old('gender') === 'female') required> {{ __('female') }}</label>
                                    </fieldset>
                                    <x-page.field name="dob" id="new-staff-dob" type="date" :label="__('Date of birth')" :max="now()->toDateString()" required />
                                    <x-m3.file-field name="image" id="new-staff-image" :label="__('Staff photo')" accept="image/jpeg,image/png" :hint="__('Choose a JPEG or PNG up to 2 MB.')" required />
                                    <x-page.field name="address" id="new-staff-address" type="textarea" :label="__('Address')" autocomplete="street-address" required />
                                </div>
                                <div class="panel-actions"><button class="m3-button m3-button--filled" type="submit">{{ __('Create staff invitation') }}</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @endif
            @if(request('view') !== 'create')
            @can('staff-list')
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('staff') }}
                            </h4>
                            <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                data-url="{{ route('staff.show', 1) }}" data-click-to-select="true" data-search="true"
                                data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                data-search="false" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                data-export-types='["txt","excel"]'
                                data-export-options='{ "fileName": "staff-list-<?= date('d-m-y') ?>" ,"ignoreColumn":
                                ["operate"]}' data-escape="true">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="user_id" data-sortable="false" data-visible="false">
                                            {{ __('user_id') }}</th>
                                        <th scope="col" data-field="role_id" data-sortable="false" data-visible="false">
                                            {{ __('role_id') }}</th>
                                        <th scope="col" data-field="first_name" data-sortable="false" data-escape="false"
                                            data-width="260" data-min-width="240" data-formatter="userInfoFormatter">
                                            {{ __('name') }}</th>
                                        <th scope="col" data-field="roles" data-sortable="false">{{ __('role') }}</th>
                                        <th scope="col" data-field="mobile" data-sortable="false">{{ __('mobile') }}
                                        </th>
                                        <th scope="col" data-field="dob" data-sortable="false">{{ __('dob') }}</th>
                                        <th scope="col" data-field="gender" data-sortable="false"
                                            data-escape="false" data-formatter="genderFormatter">{{ __('gender') }}
                                        </th>
                                        <th scope="col" data-field="address" data-sortable="false">{{ __('address') }}
                                        </th>
                                        @can('staff-edit')
                                            <th scope="col" data-escape="false" data-field="operate" data-sortable="false"
                                                data-events="staffEvents">{{ __('action') }}</th>
                                        @endcan

                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            @endcan


            @endif
            {{-- Edit Modal Starts --}}
            <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
                aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('staff') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true"><i class="fa fa-close"></i></span>
                            </button>
                        </div>
                        <form class="staff-edit-form" id="staff-edit-form" action="{{ route('staff.update', 1) }}"
                            novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="user_id" id="user_id">
                                <input type="hidden" name="edit_id" id="edit_id">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label> {{ __('role') }} <span class="text-danger">*</span></label>
                                        <select name="role_id" id="role_id" class="form-control">
                                            <option value="">{{ __('select') . ' ' . __('role') }}</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('first_name', null, [
                                            'required',
                                            'placeholder' => __('first_name'),
                                            'class' => 'form-control',
                                            'id' => 'first_name',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('last_name', null, [
                                            'required',
                                            'placeholder' => __('last_name'),
                                            'class' => 'form-control',
                                            'id' => 'last_name',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('email') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('email', null, [
                                            'required',
                                            'placeholder' => __('email'),
                                            'class' => 'form-control',
                                            'id' => 'email',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('mobile') }} <span class="text-danger">*</span></label>
                                        {!! Form::number('mobile', null, [
                                            'required',
                                            'placeholder' => __('mobile'),
                                            'class' => 'form-control mobile',
                                            'min' => 10,
                                            'id' => 'mobile',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('gender', 'male', null, ['required', 'class' => 'form-check-input edit', 'id' => 'gender']) !!}
                                                    {{ __('male') }}
                                                <i class="input-helper"></i>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('gender', 'female', null, ['required', 'class' => 'form-check-input edit', 'id' => 'gender']) !!}
                                                    {{ __('female') }}
                                                <i class="input-helper"></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('dob', null, [
                                            'required',
                                            'placeholder' => __('dob'),
                                            'class' => 'datepicker-popup-no-future form-control',
                                            'id' => 'dob',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('address') }} <span class="text-danger">*</span></label>
                                        {!! Form::textarea('address', null, [
                                            'required',
                                            'placeholder' => __('address'),
                                            'class' => 'form-control address',
                                            'rows' => 3,
                                            'id' => 'address',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('image') }}</label>
                                        <input type="file" name="image" class="file-upload-default"
                                            accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" disabled=""
                                                placeholder="{{ __('image') }}" required="required" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <small class="form-text text-muted">{{ __('image_size_hint') }}</small>
                                        <div style="width: 100px; margin-top: 10px">
                                            <img src="" id="edit-staff-image" class="img-fluid w-100" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('cancel') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('edit') }} />
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
        window.staffEvents = {
            'click .edit-data': function(e, value, row, index) {

                $('#user_id').val(row.user_id);
                $('#edit_id').val(row.id);
                $('#first_name').val(row.first_name);
                $('#last_name').val(row.last_name);
                $('#email').val(row.email);
                $('#mobile').val(row.mobile);
                $('#dob').val(row.dob);
                $('#role_id').val(row.role_id);
                $('#edit-staff-image').attr('src', row.image);

                $('#address').val(row.address);
                $('input[name=gender][value=' + row.gender + '].edit').prop('checked', true);

            }
        };
    </script>
@endsection
