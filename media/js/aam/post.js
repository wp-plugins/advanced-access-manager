/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

(function ($) {

    /**
     * Table extra filter
     * 
     * @type Object
     */
    var filter = {
        type: null
    };

    /**
     * Ajax query queue
     * 
     * Is used to get the posts/terms breadcrumbs
     * 
     * @type Array
     */
    var queue = new Array();

    /**
     * 
     * @param {type} type
     * @param {type} id
     * @param {type} title
     * @returns {undefined}
     */
    function addBreadcrumbLevel(type, id, title) {
        var level = $((type === 'type' ? '<a/>' : '<span/>')).attr({
            'href': '#',
            'data-level': type,
            'data-id': id
        }).html('<i class="icon-angle-double-right"></i>' + title);
        $('.aam-post-breadcrumb').append(level);
    }

    /**
     * 
     * @param {type} object
     * @param {type} id
     * @param {type} btn
     * @returns {undefined}
     */
    function loadAccessForm(object, id, btn) {
        //reset the form first
        var container = $('.aam-post-manager[data-type="' + object + '"]');
        
        $('.aam-row-action', container).each(function() {
            $(this).attr({
                'class' : 'aam-row-action text-muted icon-check-empty',
                'data-type' : object,
                'data-id' : id
            });
        });
        
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aam',
                sub_action: 'Post.getAccess',
                _ajax_nonce: aamLocal.nonce,
                type: object,
                id: id,
                subject: aam.getSubject().type,
                subjectId: aam.getSubject().id
            },
            beforeSend: function () {
                $(btn).attr('class', 'aam-row-action icon-spin4 animate-spin');
            },
            success: function (response) {
                //iterate through each property
                for (var property in response) {
                    var checked = (response[property] ? 'text-danger icon-check' : 'text-muted icon-check-empty');

                    $('[data-property="' + property + '"]', container).attr({
                        'class' : 'aam-row-action ' + checked
                    });
                }

                $('#post-list_wrapper').addClass('aam-hidden');
                container.addClass('active');
            },
            error: function () {
                aam.notification('danger', aam.__('Application Error'));
            },
            complete: function () {
                $(btn).attr('class', 'aam-row-action text-info icon-cog');
            }
        });
    }
    
    /**
     * 
     * @param {type} param
     * @param {type} value
     * @param {type} object
     * @param {type} object_id
     * @returns {unresolved}
     */
    function save(param, value, object, object_id) {
        var result = null;
        
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            async: false,
            data: {
                action: 'aam',
                sub_action: 'Post.save',
                _ajax_nonce: aamLocal.nonce,
                subject: aam.getSubject().type,
                subjectId: aam.getSubject().id,
                param: param,
                value: value,
                object: object,
                objectId: object_id
            },
            success: function (response) {
                if (response.status === 'failure') {
                    aam.notification('danger', response.error);
                }
                result = response;
            },
            error: function () {
                aam.notification('danger', aam.__('Application Error'));
            }
        });
        
        return result;
    };
    
    /**
     * 
     * @returns {undefined}
     */
    function initialize() {
        //initialize the role list table
        $('#post-list').DataTable({
            autoWidth: false,
            ordering: false,
            pagingType: 'simple',
            serverSide: false,
            ajax: {
                url: aamLocal.ajaxurl,
                type: 'POST',
                data: function () {
                    return {
                        action: 'aam',
                        sub_action: 'Post.getTable',
                        _ajax_nonce: aamLocal.nonce,
                        subject: aam.getSubject().type,
                        subjectId: aam.getSubject().id,
                        type: filter.type
                    };
                }
            },
            columnDefs: [
                {visible: false, targets: [0, 1]}
            ],
            language: {
                search: '_INPUT_',
                searchPlaceholder: aam.__('Search'),
                info: aam.__('_TOTAL_ object(s)'),
                infoFiltered: '',
                lengthMenu: '_MENU_'
            },
            drawCallback: function () {
                setTimeout(function () {
                    if (queue.length) {
                        queue.pop().call();
                    }
                }, 700);
            },
            initComplete: function () {
                //reset the ajax queue
                queue = new Array();
            },
            createdRow: function (row, data) {
                //object type icon
                switch (data[2]) {
                    case 'type':
                        $('td:eq(0)', row).html('<i class="icon-box"></i>');
                        break;

                    case 'term':
                        $('td:eq(0)', row).html('<i class="icon-folder"></i>');
                        break;

                    default:
                        $('td:eq(0)', row).html('<i class="icon-doc-text-inv"></i>');
                        break;
                }

                //update the title to a link
                if (data[2] === 'type') {
                    var link = $('<a/>', {
                        href: '#'
                    }).bind('click', function (event) {
                        event.preventDefault();
                        //visual feedback - show loading icon
                        $('td:eq(0)', row).html(
                                '<i class="icon-spin4 animate-spin"></i>'
                        );
                        //set filter
                        filter[data[2]] = data[0];
                        //reset the ajax queue
                        queue = new Array();
                        //finally reload the data
                        $('#post-list').DataTable().ajax.reload();

                        //update the breadcrumb
                        addBreadcrumbLevel('type', data[0], data[3]);

                    }).html(data[3]);
                    $('td:eq(1)', row).html(link);
                }

                //add breadcrumb but only if not a type
                if (data[2] !== 'type') {
                    queue.push(function () {
                        $.ajax(aamLocal.ajaxurl, {
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'aam',
                                sub_action: 'Post.getBreadcrumb',
                                _ajax_nonce: aamLocal.nonce,
                                type: data[2],
                                id: data[0]
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('td:eq(1) span', row).html(
                                            response.breadcrumb
                                            );
                                }
                            },
                            error: function () {
                                $('td:eq(1) span', row).html(aam.__('Failed'));
                            },
                            complete: function () {
                                if (queue.length) {
                                    queue.pop().call();
                                }
                            }
                        });
                    });
                    $('td:eq(1)', row).append(
                            $('<span/>', {'class': 'aam-row-subtitle'}).text(
                                aam.__('Loading...')
                            )
                   );
                }

                //update the actions
                var actions = data[4].split(',');

                var container = $('<div/>', {'class': 'aam-row-actions'});
                $.each(actions, function (i, action) {
                    switch (action) {
                        case 'manage':
                            $(container).append($('<i/>', {
                                'class': 'aam-row-action text-info icon-cog'
                            }).bind('click', function () {
                                loadAccessForm(data[2], data[0], $(this));
                                addBreadcrumbLevel('edit', data[2], data[3]);
                            }));
                            break;

                        case 'edit' :
                            $(container).append($('<i/>', {
                                'class': 'aam-row-action text-warning icon-pencil'
                            }).bind('click', function () {
                                window.open(data[1], '_blank');
                            }));
                            break;
                            break;

                        default:
                            break;
                    }
                });
                $('td:eq(2)', row).html(container);
            }
        });

        //initialize the breadcrumb
        $('.aam-post-breadcrumb').delegate('a', 'click', function (event) {
            event.preventDefault();

            //stop any pending ajax calls
            queue = new Array();

            filter.type = $(this).data('id');
            $('#post-list').DataTable().ajax.reload();
            $(this).nextAll().remove();
            $('.aam-post-manager').removeClass('active');
            $('#post-list_wrapper').removeClass('aam-hidden');
        });

        //go back button
        $('.aam-post-manager').delegate('.post-back', 'click', function (event) {
            event.preventDefault();

            var type = $(this).parent().data('type');

            $('.aam-post-manager[data-type="' + type + '"]').removeClass('active');
            $('#post-list_wrapper').removeClass('aam-hidden');
            $('.aam-post-breadcrumb span:last').remove();
        });

        //initialize each access property
        $('.aam-post-manager').delegate('.aam-row-action', 'click', function (event) {
            event.preventDefault();
            
            var checked = !$(this).hasClass('icon-check');
            
            $(this).attr('class', 'aam-row-action icon-spin4 animate-spin');
            var response = save(
                    $(this).data('property'),
                    checked,
                    $(this).data('type'),
                    $(this).data('id')
            );
            if (response.status === 'success') {
                if (checked) {
                    $(this).attr(
                        'class', 'aam-row-action text-danger icon-check'
                    );
                } else {
                    $(this).attr(
                        'class', 'aam-row-action text-muted icon-check-empty'
                    );
                }
            }
        });
    }

    aam.addHook('init', initialize);

})(jQuery);