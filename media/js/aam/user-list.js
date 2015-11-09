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
        
        return (subject.type === 'user' && parseInt(subject.id) === id);
    }
    
    /**
     * 
     * @param {type} id
     * @param {type} btn
     * @returns {undefined}
     */
    function blockUser(id, btn) {
        var state = ($(btn).hasClass('icon-lock') ? 0 : 1);
        
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'User.block',
                _ajax_nonce: aamLocal.nonce,
                subject: 'user',
                subjectId: id
            },
            beforeSend: function () {
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
            },
            success: function (response) {
                if (response.status === 'success') {
                    if (state === 1) {
                        $(btn).attr('class', 'aam-row-action icon-lock text-danger');
                    } else {
                        $(btn).attr('class', 'aam-row-action icon-lock-open-alt text-warning');
                    }
                } else {
                    aam.notification(
                        'danger', aam.__('Failed To Block User')
                    );
                }
            },
            error: function () {
                aam.notification(
                    'danger', aam.__('Application Error. Contact Support.')
                );
            }
        });
    }
    
    //initialize the user list table
    $('#user-list').DataTable({
        autoWidth: false,
        ordering: false,
        dom: 'ftrip',
        pagingType: 'simple',
        serverSide: true,
        processing: true,
        ajax: {
            url: aamLocal.ajaxurl,
            type: 'POST',
            data: {
                action: 'aam',
                sub_action: 'User.getTable',
                _ajax_nonce: aamLocal.nonce
            }
        },
        columnDefs: [
            {visible: false, targets: [0, 1]}
        ],
        language: {
            search: '_INPUT_',
            searchPlaceholder: aam.__('Search User'),
            info: aam.__('_TOTAL_ user(s)'),
            infoFiltered: ''
        },
        initComplete: function () {
             var create = $('<a/>', {
                'href'  : '#',
                'class' : 'btn btn-primary'
            }).html('<i class="icon-plus"></i> ' + aam.__('Create')).bind('click', function (event) {
                event.preventDefault();
                window.open(aamLocal.url.addUser, '_blank');
            });

            $('.dataTables_filter', '#user-list_wrapper').append(create);
        },
        createdRow: function (row, data) {
            if (isCurrent(data[0])) {
                $('td:eq(0)', row).html('<strong>' + data[2] + '</strong>');
            } else {
                $('td:eq(0)', row).html('<span>' + data[2] + '</span>');
            }
            
            //add subtitle
            $('td:eq(0)', row).append(
                $('<i/>', {'class': 'aam-row-subtitle'}).text(
                    aam.__('Role: ') + data[1]
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
                            aam.setSubject('user', data[0]);
                            $('td:eq(0) span', row).replaceWith(
                                    '<strong>' + data[2] + '</strong>'
                            );
                            $('i.icon-cog', container).attr('class', 'aam-row-action icon-spin4 animate-spin');
                            aam.fetchContent();
                            $('i.icon-spin4', container).attr('class', 'aam-row-action icon-cog text-info');
                            //make sure that there is no way user add's new capability
                            $('#add-capability').hide();
                        }));
                        break;

                    case 'edit':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-pencil text-warning'
                        }).bind('click', function () {
                            window.open(
                                aamLocal.url.editUser + '?user_id=' + data[0], '_blank'
                            );
                        }));
                        break;

                    case 'lock':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-lock-open-alt text-warning'
                        }).bind('click', function () {
                            blockUser(data[0], $(this));
                        }));
                        break;
                        
                    case 'unlock':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-lock text-danger'
                        }).bind('click', function () {
                            blockUser(data[0], $(this));
                        }));
                        break;
                        
                      case 'no-lock':
                      case 'no-unlock':
                        $(container).append($('<i/>', {
                            'class': 'aam-row-action icon-lock text-muted'
                        }).bind('click', function () {
                            $('#user-notification-modal').modal('show');
                        }));
                        break;
                        
                    default:
                        break;
                }
            });
            $('td:eq(1)', row).html(container);
        }
    });
    
    //add setSubject hook
    aam.addHook('setSubject', function () {
        //clear highlight
        $('tbody tr', '#user-list').each(function () {
            if ($('strong', $(this)).length) {
                var highlight = $('strong', $(this));
                highlight.replaceWith('<span>' + highlight.text() + '</span>');
            }
        });
    });
    
    //in case interface needed to be reloaded
    aam.addHook('refresh', function () {
        $('#user-list').DataTable().ajax.url(aamLocal.ajaxurl).load();
    });
    
})(jQuery);