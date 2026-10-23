@extends('layouts.master')

@section('title')
    {{ __('notifications') }}
@endsection

@section('css')
    <style>
        /* Recipient counts in the tabs: the M3 large badge shape (16px,
           full round, label-small); tonal on an inactive tab, primary on the
           active one. */
        .recipients-tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border: 0;
            border-radius: var(--md-sys-shape-corner-full);
            font-size: var(--md-sys-typescale-label-small-size);
            font-weight: var(--md-sys-typescale-label-small-weight);
            line-height: 1;
            background-color: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
        }

        #recipientsTabs .nav-link.active .recipients-tab-badge {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('notifications') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-12 col-lg-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h4 class="card-title">
                                    {{ __('create') . ' ' . __('notifications') }}
                                </h4>
                            </div>
                        </div>
                        <form class="pt-3 create-notification" id="create_notification" method="POST"
                            novalidate="novalidate" action="{{ route('notifications.store') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-12 col-sm-6">
                                    <label>{{ __('send_to') }} <span class="text-danger">*</span></label>
                                    <select name="send_to[]" id="send_to" multiple
                                        data-placeholder="{{ __('Please Select') }}"
                                        class="form-control send_to js-example-basic-single select2-hidden-accessible">
                                        <option value="1">{{ __('all_users') }}</option>
                                        <option value="3">{{ __('students') }}</option>
                                        <option value="4">{{ __('parents') }}</option>
                                        <option value="5">{{ __('teacher') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-12 col-sm-6">
                                    <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                    <input name="title" maxlength="128" type="text" placeholder="{{ __('title') }}"
                                        class="form-control" />
                                </div>
                                <div class="form-group col-12">
                                    <label>{{ __('message') }} <span class="text-danger">*</span></label>
                                    <textarea name="message" maxlength="128" cols="10" rows="3" placeholder="{{ __('message') }}" class="form-control"></textarea>
                                </div>
                                <div class="form-group col-12">
                                    <label><input type="checkbox" name="urgent" value="1"> {{ __('Urgent notice (required for recipients)') }}</label>
                                </div>
                                <div class="form-group col-12">
                                    <label>{{ __('image') }}</label>
                                    <input type="file" name="image" class="file-upload-default" accept="image/*" />
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled=""
                                            placeholder="{{ __('image') }}" />
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme"
                                                type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                                <div id="selected_recipients_inputs"></div>
                                <div class="col-12">
                                    <input class="btn btn-theme" type="submit" value="{{ __('submit') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('recipients') }}</h4>

                        <div id="recipients_empty_state" class="text-center" style="padding: 2.5rem 1rem; color: var(--md-sys-color-on-surface-variant);">
                            <div style="font-size: 2.5rem; color: var(--md-sys-color-primary); opacity: 0.8;">
                                <i class="fa fa-users"></i>
                            </div>
                            <h5 id="recipients_empty_title" class="mt-3" style="color: var(--md-sys-color-on-surface); font-weight: 500;">
                                {{ __('select_a_recipient_group') }}
                            </h5>
                            <p id="recipients_empty_message" class="mb-0"
                                style="color: var(--md-sys-color-on-surface-variant); max-width: 320px; margin-left: auto; margin-right: auto;">
                                {{ __('choose_recipient_group_help') }}
                            </p>
                        </div>

                        <div id="recipients_tabs_wrapper" style="display: none;">
                            <ul class="nav nav-tabs" id="recipientsTabs" role="tablist">
                                <li class="nav-item recipients-tab-item" data-role="students" style="display: none;">
                                    <a class="nav-link" id="tab-link-students" data-toggle="tab" href="#tab-students"
                                        role="tab" aria-controls="tab-students" aria-selected="false">
                                        {{ __('students') }}
                                        <span class="badge badge-pill ml-1 recipients-tab-badge" data-role="students"
                                            style="display: none;">0</span>
                                    </a>
                                </li>
                                <li class="nav-item recipients-tab-item" data-role="parents" style="display: none;">
                                    <a class="nav-link" id="tab-link-parents" data-toggle="tab" href="#tab-parents"
                                        role="tab" aria-controls="tab-parents" aria-selected="false">
                                        {{ __('parents') }}
                                        <span class="badge badge-pill ml-1 recipients-tab-badge" data-role="parents"
                                            style="display: none;">0</span>
                                    </a>
                                </li>
                                <li class="nav-item recipients-tab-item" data-role="teachers" style="display: none;">
                                    <a class="nav-link" id="tab-link-teachers" data-toggle="tab" href="#tab-teachers"
                                        role="tab" aria-controls="tab-teachers" aria-selected="false">
                                        {{ __('teachers') }}
                                        <span class="badge badge-pill ml-1 recipients-tab-badge" data-role="teachers"
                                            style="display: none;">0</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content pt-3">
                                <div class="tab-pane fade" id="tab-students" role="tabpanel"
                                    aria-labelledby="tab-link-students">
                                    <table class="table" id="recipients_students_table" data-toggle="table"
                                        data-url="{{ route('notifications.recipients') }}" data-side-pagination="server"
                                        data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-page-size="10"
                                        data-search="true" data-show-refresh="true" data-trim-on-search="false"
                                        data-mobile-responsive="true" data-maintain-selected="true" data-id-field="id"
                                        data-unique-id="id" data-click-to-select="true"
                                        data-query-params="recipientsStudentsQuery">
                                        <thead>
                                            <tr>
                                                <th data-field="state" data-checkbox="true"></th>
                                                <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                                <th data-field="no">{{ __('no.') }}</th>
                                                <th data-field="user" data-formatter="recipientUserFormatter">
                                                    {{ __('user') }}
                                                </th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="tab-parents" role="tabpanel"
                                    aria-labelledby="tab-link-parents">
                                    <table class="table" id="recipients_parents_table" data-toggle="table"
                                        data-url="{{ route('notifications.recipients') }}" data-side-pagination="server"
                                        data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-page-size="10"
                                        data-search="true" data-show-refresh="true" data-trim-on-search="false"
                                        data-mobile-responsive="true" data-maintain-selected="true" data-id-field="id"
                                        data-unique-id="id" data-click-to-select="true"
                                        data-query-params="recipientsParentsQuery">
                                        <thead>
                                            <tr>
                                                <th data-field="state" data-checkbox="true"></th>
                                                <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                                <th data-field="no">{{ __('no.') }}</th>
                                                <th data-field="user" data-formatter="recipientUserFormatter">
                                                    {{ __('user') }}
                                                </th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="tab-teachers" role="tabpanel"
                                    aria-labelledby="tab-link-teachers">
                                    <table class="table" id="recipients_teachers_table" data-toggle="table"
                                        data-url="{{ route('notifications.recipients') }}" data-side-pagination="server"
                                        data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-page-size="10"
                                        data-search="true" data-show-refresh="true" data-trim-on-search="false"
                                        data-mobile-responsive="true" data-maintain-selected="true" data-id-field="id"
                                        data-unique-id="id" data-click-to-select="true"
                                        data-query-params="recipientsTeachersQuery">
                                        <thead>
                                            <tr>
                                                <th data-field="state" data-checkbox="true"></th>
                                                <th data-field="id" data-visible="false">{{ __('id') }}</th>
                                                <th data-field="no">{{ __('no.') }}</th>
                                                <th data-field="user" data-formatter="recipientUserFormatter">
                                                    {{ __('user') }}
                                                </th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('notifications') }}
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ route('notifications.show', 1) }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                    data-mobile-responsive="true" data-use-row-attr-func="true"
                                    data-reorderable-rows="false" data-maintain-selected="true"
                                    data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "{{ __('notifications') }}-<?= date(' d-m-y') ?>"
                                    ,"ignoreColumn":["operate"]}' data-show-export="true"
                                    data-query-params="notificationQueryParams" data-escape="true"
                                    data-escape-title="false">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true"
                                                data-visible="false">
                                                {{ __('id') }}</th>
                                            <th scope="col" data-field="no">{{ __('no.') }}</th>
                                            <th scope="col" data-field="title" data-sortable="true">
                                                {{ __('title') }}
                                            </th>
                                            <th scope="col" data-field="message" data-sortable="true">
                                                {{ __('message') }}
                                            </th>
                                            <th scope="col" data-field="image" data-sortable="true"
                                                data-formatter="notificationImageFormatter">{{ __('image') }}</th>
                                            <th scope="col" data-field="type" data-sortable="true"
                                                data-formatter="notificationTypeFormatter">
                                                {{ __('type') }}
                                            </th>
                                            <th scope="col" data-field="date" data-sortable="true">
                                                {{ __('date') }}
                                            </th>
                                            <th scope="col" data-field="created_at" data-sortable="true"
                                                data-visible="false">{{ __('created_at') }}</th>
                                            <th scope="col" data-field="updated_at" data-sortable="true"
                                                data-visible="false">{{ __('updated_at') }}</th>
                                            <th scope="col" data-field="operate" data-sortable="false"
                                                data-escape="false">
                                                {{ __('action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('script')
    <script>
        var RECIPIENT_ROLES = ['students', 'parents', 'teachers'];
        var SEND_TO_TO_ROLE = {
            '3': 'students',
            '4': 'parents',
            '5': 'teachers'
        };

        (function() {
            var $sendTo = $('#send_to');
            var $wrapper = $('#recipients_tabs_wrapper');
            var $emptyState = $('#recipients_empty_state');
            var $emptyTitle = $('#recipients_empty_title');
            var $emptyMessage = $('#recipients_empty_message');
            var $hiddenInputs = $('#selected_recipients_inputs');

            var $tables = {
                students: $('#recipients_students_table'),
                parents: $('#recipients_parents_table'),
                teachers: $('#recipients_teachers_table')
            };

            // Per-role selection cache, persists across pagination, search, tab switches.
            var selected = {
                students: {},
                parents: {},
                teachers: {}
            };
            // Track which roles are currently picked in the send_to multi-select.
            var activeRoles = [];
            var broadcastMode = false;

            function selectedCount(role) {
                return Object.keys(selected[role]).length;
            }

            function totalSelected() {
                return RECIPIENT_ROLES.reduce(function(sum, r) {
                    return sum + selectedCount(r);
                }, 0);
            }

            function syncHiddenInputs() {
                $hiddenInputs.empty();
                if (broadcastMode) return;
                RECIPIENT_ROLES.forEach(function(role) {
                    if (activeRoles.indexOf(role) === -1) return;
                    Object.keys(selected[role]).forEach(function(id) {
                        $hiddenInputs.append('<input type="hidden" name="user_id[]" value="' + id +
                            '">');
                    });
                });
            }

            function updateTabBadge(role) {
                var $badge = $('.recipients-tab-badge[data-role="' + role + '"]');
                var c = selectedCount(role);
                if (c > 0) {
                    $badge.text(c).show();
                } else {
                    $badge.hide();
                }
            }

            function showEmptyState(title, message) {
                $wrapper.hide();
                $emptyTitle.text(title);
                $emptyMessage.text(message);
                $emptyState.show();
            }

            function showTabs() {
                $emptyState.hide();
                $wrapper.show();
            }

            function activateFirstVisibleTab() {
                // If the currently-active tab is still valid, leave it alone.
                var currentActiveRole = $('#recipientsTabs .nav-link.active')
                    .attr('href');
                if (currentActiveRole) {
                    currentActiveRole = currentActiveRole.replace('#tab-', '');
                    if (activeRoles.indexOf(currentActiveRole) !== -1) {
                        $tables[currentActiveRole].bootstrapTable('resetView');
                        return;
                    }
                }

                // Otherwise activate the first picked role's tab.
                $('#recipientsTabs .nav-link').removeClass('active');
                $('#recipientsTabs .nav-link').attr('aria-selected', 'false');
                $('.tab-content > .tab-pane').removeClass('show active');

                for (var i = 0; i < RECIPIENT_ROLES.length; i++) {
                    var role = RECIPIENT_ROLES[i];
                    if (activeRoles.indexOf(role) === -1) continue;
                    $('#tab-link-' + role).addClass('active').attr('aria-selected', 'true');
                    $('#tab-' + role).addClass('show active');
                    $tables[role].bootstrapTable('resetView');
                    break;
                }
            }

            function refreshActiveRoleTables() {
                activeRoles.forEach(function(role) {
                    $tables[role].bootstrapTable('refresh', {
                        silent: false
                    });
                });
            }

            function applyMode() {
                var values = ($sendTo.val() || []).map(String);
                broadcastMode = values.indexOf('1') !== -1;

                // Determine which role tabs to show.
                activeRoles = [];
                if (!broadcastMode) {
                    values.forEach(function(v) {
                        var role = SEND_TO_TO_ROLE[v];
                        if (role && activeRoles.indexOf(role) === -1) activeRoles.push(role);
                    });
                }

                // Drop selections for roles no longer picked.
                RECIPIENT_ROLES.forEach(function(role) {
                    if (activeRoles.indexOf(role) === -1) selected[role] = {};
                    updateTabBadge(role);
                });

                if (values.length === 0) {
                    syncHiddenInputs();
                    showEmptyState(
                        "{{ __('select_a_recipient_group') }}",
                        "{{ __('choose_recipient_group_help') }}"
                    );
                    return;
                }

                if (broadcastMode) {
                    syncHiddenInputs();
                    showEmptyState(
                        "{{ __('broadcast_to_all_users') }}",
                        "{{ __('broadcast_to_all_users_help') }}"
                    );
                    return;
                }

                // Toggle visibility of each tab list-item based on picks.
                RECIPIENT_ROLES.forEach(function(role) {
                    $('.recipients-tab-item[data-role="' + role + '"]')
                        .toggle(activeRoles.indexOf(role) !== -1);
                });

                showTabs();
                activateFirstVisibleTab();
                refreshActiveRoleTables();
                syncHiddenInputs();
            }

            // Wire selection events for each role's table.
            RECIPIENT_ROLES.forEach(function(role) {
                var $t = $tables[role];

                $t.on('post-body.bs.table', function() {
                    var ids = Object.keys(selected[role]).map(function(id) {
                        return parseInt(id, 10);
                    });
                    if (ids.length === 0) return;
                    $t.bootstrapTable('checkBy', {
                        field: 'id',
                        values: ids
                    });
                });

                $t.on('check.bs.table', function(e, row) {
                    selected[role][row.id] = true;
                    updateTabBadge(role);
                    syncHiddenInputs();
                });

                $t.on('uncheck.bs.table', function(e, row) {
                    delete selected[role][row.id];
                    updateTabBadge(role);
                    syncHiddenInputs();
                });

                $t.on('check-all.bs.table', function(e, rowsAfter) {
                    rowsAfter.forEach(function(row) {
                        selected[role][row.id] = true;
                    });
                    updateTabBadge(role);
                    syncHiddenInputs();
                });

                $t.on('uncheck-all.bs.table', function(e, rowsAfter, rowsBefore) {
                    rowsBefore.forEach(function(row) {
                        delete selected[role][row.id];
                    });
                    updateTabBadge(role);
                    syncHiddenInputs();
                });
            });

            // Fix zero-width tables inside inactive tabs when user switches tabs.
            $('#recipientsTabs').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (!target) return;
                var role = target.replace('#tab-', '');
                if ($tables[role]) {
                    $tables[role].bootstrapTable('resetView');
                }
            });

            $sendTo.on('change', applyMode);

            // Guard form submit — runs in capture phase so it fires BEFORE the
            // existing jQuery bubble-phase submit handler in custom.js that
            // dispatches the AJAX request.
            document.getElementById('create_notification').addEventListener('submit', function(e) {
                var values = ($sendTo.val() || []);
                var msg = null;
                if (values.length === 0) {
                    msg = @json(__('please_select_send_to_group'));
                } else if (!broadcastMode && totalSelected() === 0) {
                    msg = @json(__('please_select_at_least_one_user'));
                }
                if (msg) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    showErrorToast(msg);
                }
            }, true);

            applyMode();
        })();

        function recipientsQueryParamsFor(p, recipientType) {
            return {
                limit: p.limit,
                offset: p.offset,
                search: p.search,
                recipient_type: recipientType
            };
        }

        function recipientsStudentsQuery(p) {
            return recipientsQueryParamsFor(p, 'students');
        }

        function recipientsParentsQuery(p) {
            return recipientsQueryParamsFor(p, 'parents');
        }

        function recipientsTeachersQuery(p) {
            return recipientsQueryParamsFor(p, 'teachers');
        }

        function recipientUserFormatter(value, row) {
            var fallback = "{{ asset('assets/no-image.svg') }}";
            var img = row.image || fallback;
            var name = (row.first_name || '') + ' ' + (row.last_name || '');
            var email = row.email || '';
            return '<div class="d-flex align-items-center">' +
                '<img src="' + img + '" alt="" onerror="onErrorImage(event)" ' +
                'style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid var(--md-sys-color-outline-variant);">' +
                '<div>' +
                '<div style="font-weight: 500; color: var(--md-sys-color-on-surface);">' + $('<div>').text(name.trim()).html() + '</div>' +
                '<div style="font-size: 0.8rem; color: var(--md-sys-color-on-surface-variant);">' + $('<div>').text(email).html() + '</div>' +
                '</div>' +
                '</div>';
        }
    </script>
@endsection
