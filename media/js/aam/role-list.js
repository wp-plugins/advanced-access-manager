/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

(function ($) {
    
    /**
     * 
     * @param {type} id
     * @returns {Boolean}
     */
    function isCurrent(id) {
        var subject = aam.getSubject();
        
        return (subject.type === 'role' && subject.id === id);
    }

    /**
     * 
     * @returns {undefined}
     */
    function fetchRoleList() {
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'Role.getList',
                _ajax_nonce: aamLocal.nonce
            },
            beforeSend: function () {
                $('#inherit-role-list').html(
                    '<option value="">' + aam.__('Loading...') + '</option>'
                );
            },
            success: function (response) {
                $('#inherit-role-list').html(
                        '<option value="">' + aam.__('Select Role') + '</option>'
                );
                for (var i in response) {
                    $('#inherit-role-list').append(
                        '<option value="' + i + '">' + response[i].name + '</option>'
                    );
                }
            }
        });
    }

    //initialize the role list table
    $('#role-list').DataTable({
        autoWidth: false,
        ordering: false,
        dom: 'ftrip',
        pagingType: 'simple',
        processing: true,
        serverSide: false,
        ajax: {
            url: aamLocal.ajaxurl,
            type: 'POST',
            data: {
                action: 'aam',
                sub_action: 'Role.getTable',
                _ajax_nonce: aamLocal.nonce
            }
        },
        columnDefs: [
            {visible: false, targets: [0, 1]}
        ],
        language: {
            search: '_INPUT_',
            searchPlaceholder: aam.__('Search Role'),
            info: aam.__('_TOTAL_ role(s)'),
            infoFiltered: ''
        },
        initComplete: function () {
            var create = $('<a/>', {
                'href'  : '#',
                'class' : 'btn btn-primary'
            }).html('<i class="icon-plus"></i> ' + aam.__('Create'))
              .bind('click', function (event) {
                event.preventDefault();
                
                //clear add role form first
                $('#new-role-name', '#add-role-modal').val('');
                fetchRoleList();

                $('#add-role-modal').modal('show').on('shown.bs.modal', function (e) {
                    $('#new-role-name', '#add-role-modal').focus();
                });
            });

            $('.dataTables_filter', '#role-list_wrapper').append(create);
        },
        createdRow: function (row, data) {
            if (isCurrent(data[0])) {
                $('td:eq(0)', row).html('<strong>' + data[2] + '</strong>');
            } else {
                $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
            }
            
            //if administrator, highlight
            if (data[0] === 'administrator') {
                $('td:eq(0) span, td:eq(0) strong', row).addClass('aam-highlight');
            }
            
            //add subtitle
            $('td:eq(0)', row).append(
                $('<i/>', {'class': 'aam-row-subtitle'}).text(
                    aam.__('Users: ') + parseInt(data[1])
                )
            );
            
            var actions = data[3].split(',');

            var container = $('<div/>', {'class': 'aam-row-actions'});
            $.each(actions, function (i, action) {
                switch (action) {
                    case 'manage':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-cog text-info'
                        }).bind('click', function() {
                            aam.setSubject('role', data[0]);
                            $('td:eq(0) span', row).replaceWith(
                                    '<strong>' + data[2] + '</strong>'
                            );
                            $('i.icon-cog', container).attr(
                                'class', 'aam-row-action icon-spin4 animate-spin'
                            );
                            aam.fetchContent();
                            $('i.icon-spin4', container).attr(
                                'class', 'aam-row-action icon-cog text-info'
                            );
                            //Show add capability that may be hidden after manager user
                            $('#add-capability').show();
                        }));
                        break;

                    case 'edit':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-pencil text-warning'
                        }).bind('click', function () {
                            $('#edit-role-btn').data('role', data[0]);
                            $('#edit-role-name').val(data[2]);
                            $('#edit-role-modal').modal('show').on('shown.bs.modal', function () {
                                $('#edit-role-name').focus();
                            });
                        }));
                        break;

                    case 'delete':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-trash-empty text-danger'
                        }).bind('click', {role: data}, function (event) {
                            $('#delete-role-btn').data('role', data[0]);
                            var message = $('#delete-role-modal .aam-confirm-message').data('message');
                            $('#delete-role-modal .aam-confirm-message').html(
                                message.replace(
                                    '%s',
                                    '<strong>' + event.data.role[2] + '</strong>'
                                )
                            );

                            $('#delete-role-modal').modal('show');
                        }));
                        break;

                    case 'no-delete':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-trash-empty text-muted'
                        }).bind('click', function () {
                            $('#role-notification-modal').modal('show');
                        }));
                        break;

                    default:
                        break;
                }
            });
            $('td:eq(1)', row).html(container);
        }
    });

    //add role button
    $('#add-role-btn').bind('click', function (event) {
        event.preventDefault();

        var _this = this;

        $('#new-role-name', '#add-role-modal').parent().removeClass('has-error');

        var name = $.trim($('#new-role-name', '#add-role-modal').val());
        var inherit = $.trim($('#inherit-role-list', '#add-role-modal').val());

        if (name) {
            $.ajax(aamLocal.ajaxurl, {
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'aam',
                    sub_action: 'Role.add',
                    _ajax_nonce: aamLocal.nonce,
                    name: name,
                    inherit: inherit
                },
                beforeSend: function () {
                    $(_this).text(aam.__('Saving...')).attr('disabled', true);
                },
                success: function (response) {
                    if (response.status === 'success') {
                        $('#role-list').DataTable().ajax.reload();
                        $('#add-role-modal').modal('hide');
                    } else {
                        aam.notification(
                                'danger', aam.__('Failed To Add New Role')
                        );
                    }
                },
                error: function () {
                    aam.notification('danger', aam.__('Application Error'));
                },
                complete: function () {
                    $(_this).text(aam.__('Add Role')).attr('disabled', false);
                }
            });
        } else {
            $('#new-role-name').focus().parent().addClass('has-error');
        }
    });

    //edit role button
    $('#edit-role-btn').bind('click', function (event) {
        var _this = this;

        $('#edit-role-name').parent().removeClass('has-error');
        var name = $.trim($('#edit-role-name').val());

        if (name) {
            $.ajax(aamLocal.ajaxurl, {
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'aam',
                    sub_action: 'Role.edit',
                    _ajax_nonce: aamLocal.nonce,
                    subject: 'role',
                    subjectId: $(_this).data('role'),
                    name: name
                },
                beforeSend: function () {
                    $(_this).text(aam.__('Saving...')).attr('disabled', true);
                },
                success: function (response) {
                    if (response.status === 'success') {
                        $('#role-list').DataTable().ajax.reload();
                    } else {
                        aam.notification(
                                'danger', aam.__('Failed To Update Role')
                        );
                    }
                },
                error: function () {
                    aam.notification('danger', aam.__('Application Error'));
                },
                complete: function () {
                    $('#edit-role-modal').modal('hide');
                    $(_this).text(aam.__('Update')).attr('disabled', false);
                }
            });
        } else {
            $('#edit-role-name').focus().parent().addClass('has-error');
        }
    });
    
    //edit role button
    $('#delete-role-btn').bind('click', function (event) {
        var _this = this;

        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'Role.delete',
                _ajax_nonce: aamLocal.nonce,
                subject: 'role',
                subjectId: $(_this).data('role')
            },
            beforeSend: function () {
                $(_this).text(aam.__('Deleting...')).attr('disabled', true);
            },
            success: function (response) {
                if (response.status === 'success') {
                    $('#role-list').DataTable().ajax.reload();
                } else {
                    aam.notification('danger', aam.__('Failed To Delete Role'));
                }
            },
            error: function () {
                    aam.notification('danger', aam.__('Application Error'));
            },
            complete: function () {
                $('#delete-role-modal').modal('hide');
                $(_this).text(aam.__('Delete Role')).attr('disabled', false);
            }
        });
    });
    
    //add setSubject hook
    aam.addHook('setSubject', function () {
        //clear highlight
        $('tbody tr', '#role-list').each(function () {
            if ($('strong', $(this)).length) {
                var highlight = $('strong', $(this));
                highlight.replaceWith($('<span/>').text(highlight.text()));
            }
        });
    });
    
    //in case interface needed to be reloaded
    aam.addHook('refresh', function () {
        $('#role-list').DataTable().ajax.url(aamLocal.ajaxurl).load();
    });

})(jQuery);