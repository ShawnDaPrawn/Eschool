// Staff / Teacher ID-card picker. Drives the #table_list bootstrap-table on the generate page:
// the Type dropdown swaps the listed set (teacher <-> staff), and the checkbox selection is
// collected into the hidden #user_id field that the Generate form posts.
(function () {
    'use strict';

    var $tableList = $('#table_list');
    var $typeSelect = $('#staff_id_card_type');
    var $userIdField = $('#user_id');
    var $generateBtn = $('#generate_id_btn');

    // Running selection of user ids, preserved across pagination / refresh.
    var selections = [];

    // bootstrap-table looks these up by name in the global scope (data-query-params / data-response-handler).
    window.staffIdCardQueryParams = function (params) {
        params.type = $typeSelect.val();
        return params;
    };

    window.staffIdCardResponseHandler = function (res) {
        $.each(res.rows, function (i, row) {
            row.state = $.inArray(row.id, selections) !== -1;
        });
        return res;
    };

    function syncSelection() {
        $userIdField.val(selections.join(','));
        $generateBtn.prop('disabled', selections.length === 0);
    }

    $(function () {
        $tableList.on('check.bs.table check-all.bs.table uncheck.bs.table uncheck-all.bs.table',
            function (e, rowsAfter, rowsBefore) {
                var rows = e.type === 'uncheck-all' ? rowsBefore : rowsAfter;
                var ids = $.map($.isArray(rows) ? rows : [rows], function (row) {
                    return row.id;
                });
                if ($.inArray(e.type, ['check', 'check-all']) > -1) {
                    ids.forEach(function (id) {
                        if (selections.indexOf(id) === -1) {
                            selections.push(id);
                        }
                    });
                } else {
                    selections = selections.filter(function (id) {
                        return ids.indexOf(id) === -1;
                    });
                }
                syncSelection();
            });

        // Switching Teacher <-> Staff lists a different set of users, so clear the running selection.
        $typeSelect.on('change', function () {
            selections = [];
            syncSelection();
            $tableList.bootstrapTable('refresh');
        });
    });
})();
