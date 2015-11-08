/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

(function ($) {

    /**
     * 
     * @param {type} capability
     * @param {type} btn
     * @returns {undefined}
     */
    function save(capability, btn) {
        var granted = $(btn).hasClass('icon-check-empty') ? 1 : 0;
        
        //show indicator
        $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
        
        if (aam.save(capability, granted, 'capability').status === 'success') {
            if (granted) {
                $(btn).attr('class', 'aam-row-action text-success icon-check');
            } else {
                $(btn).attr('class', 'aam-row-action text-muted icon-check-empty');
            }
        }
    }
    /**
     * 
     * @returns {undefined}
     */
    function initialize() {
        //initialize the role list table
        $('#capability-list').DataTable({
            autoWidth: false,
            ordering: false,
            pagingType: 'simple',
            serverSide: false,
            ajax: {
                url: aamLocal.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aam',
                    sub_action: 'Capability.getTable',
                    _ajax_nonce: aamLocal.nonce,
                    subject: aam.getSubject().type,
                    subjectId: aam.getSubject().id
                }
            },
            columnDefs: [
                {visible: false, targets: [0]}
            ],
            language: {
                search: '_INPUT_',
                searchPlaceholder: aam.__('Search Capability'),
                info: aam.__('_TOTAL_ capability(s)'),
                infoFiltered: '',
                lengthMenu: '_MENU_'
            },
            createdRow: function (row, data) {
                var actions = data[3].split(',');

                var container = $('<div/>', {'class': 'aam-row-actions'});
                $.each(actions, function (i, action) {
                    var checkbox = $('<input/>').attr({
                            'type': 'checkbox'
                    });
                    switch (action) {
                        case 'unchecked':
                            $(container).append($('<i/>', {
                                'class': 'aam-row-action text-muted icon-check-empty'
                            }).bind('click', function () {
                                save(data[0], this);
                            }));
                            break;

                        case 'checked':
                            $(container).append($('<i/>', {
                                'class': 'aam-row-action text-success icon-check'
                            }).bind('click', function () {
                                save(data[0], this);
                            }));
                            break;

                        default:
                            break;
                    }
                });
                $('td:eq(2)', row).html(container);
            }
        });

        //filter capability dropdown
        $('#capability-filter').bind('click', function () {
            $('.dropdown-menu').dropdown('toggle');
        });

        $('a', '#capability-groups').each(function () {
            $(this).bind('click', function () {
                var table = $('#capability-list').DataTable();
                if ($(this).data('clear') !== true) {
                    table.column(1).search($(this).text()).draw();
                } else {
                    table.column(1).search('').draw();
                }
            });
        });


        $('#add-capability').bind('click', function (event) {
            event.preventDefault();
            $('#add-capability-modal').modal('show');
        });

        $('#add-capability-btn').bind('click', function () {
            var _this = this;

            var capability = $.trim($('#new-capability-name').val());
            $('#new-capability-name').parent().removeClass('has-error');

            if (capability) {
                $.ajax(aamLocal.ajaxurl, {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'aam',
                        sub_action: 'Capability.add',
                        _ajax_nonce: aamLocal.nonce,
                        capability: capability
                    },
                    beforeSend: function () {
                        $(_this).text(aam.__('Saving...')).attr('disabled', true);
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#add-capability-modal').modal('hide');
                            $('#capability-list').DataTable().ajax.reload();
                        } else {
                            aam.notification(
                                    'danger', aam.__('Failed To Add New Capability')
                                    );
                        }
                    },
                    error: function () {
                        aam.notification('danger', aam.__('Application Error'));
                    },
                    complete: function () {
                        $(_this).text(aam.__('Add Capability')).attr('disabled', false);
                    }
                });
            } else {
                $('#new-capability-name').parent().addClass('has-error');
            }
        });

        $('#add-capability-modal').on('shown.bs.modal', function (e) {
            $('#new-capability-name').focus();
        });

    }

    aam.addHook('init', initialize);

})(jQuery);