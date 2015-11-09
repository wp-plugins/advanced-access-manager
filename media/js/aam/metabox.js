/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

(function ($) {

    /**
     * 
     * @returns {undefined}
     */
    function getContent() {
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'aam',
                sub_action: 'Metabox.getContent',
                _ajax_nonce: aamLocal.nonce,
                subject: aam.getSubject().type,
                subjectId: aam.getSubject().id
            },
            success: function (response) {
                $('#metabox-content').replaceWith(response);
                $('#metabox-content').addClass('active');
                initialize();
            },
            error: function () {
                aam.notification('danger', aam.__('Application Error'));
            }
        });
    }

    /**
     * 
     * @returns {undefined}
     */
    function initialize() {
        //init refresh list button
        $('#refresh-metabox-list').bind('click', function (event) {
            event.preventDefault();

            $.ajax(aamLocal.ajaxurl, {
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'aam',
                    sub_action: 'Metabox.refreshList',
                    _ajax_nonce: aamLocal.nonce
                },
                beforeSend: function () {
                    $('i', '#refresh-metabox-list').attr(
                            'class', 'icon-spin4 animate-spin'
                            );
                },
                success: function (response) {
                    if (response.status === 'success') {
                        getContent();
                    } else {
                        aam.notification(
                            'danger', aam.__('Failed To Retrieve Mataboxes')
                        );
                    }
                },
                error: function () {
                    aam.notification('danger', aam.__('Application Error'));
                },
                complete: function () {
                    $('i', '#refresh-metabox-list').attr(
                            'class', 'icon-arrows-cw'
                    );
                }
            });

        });
        
        $('input[type="checkbox"]', '#metabox-list').each(function () {
            $(this).bind('click', function () {
                aam.save(
                    $(this).data('metabox'), !$(this).attr('checked'), 'metabox'
                );
            });
        });
    }
    
    aam.addHook('init', initialize);

})(jQuery);