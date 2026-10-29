<div>

    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ __('edit') . ' ' . __('session_years') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>

                <form id="editdata" class="editform" action="{{ url('session-years') }}" novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('name') }}</label>
                                <input type="text" name="name" placeholder="{{ __('name') }}"
                                    class = "form-control" id="name" required>
                            </div>

                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_date" id="start_date"
                                    placeholder="{{ __('start_date') }}" class="datepicker-popup form-control" required>
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_date" placeholder="{{ __('end_date') }}"
                                    class="datepicker-popup form-control" id="end_date" required>
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>


                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </div>
                </form>
            </div>
        </div>
    </div>


</div>
