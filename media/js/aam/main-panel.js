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
    function initializeMenu() {
        //initialize the menu switch
        $('li', '#feature-list').each(function () {
            $(this).bind('click', function () {
                $('.aam-feature').removeClass('active');
                //highlight active feature
                $('li', '#feature-list').removeClass('active');
                $(this).addClass('active');
                //show feature content
                $('#' + $(this).data('feature') + '-content').addClass('active');
            });
        });

        $('li:eq(0)', '#feature-list').trigger('click');
    }

    /**
     * 
     * @returns {undefined}
     */
    aam.fetchContent = function () {
        $.ajax(aamLocal.url.site, {
            type: 'POST',
            dataType: 'html',
            async: false,
            data: {
                action: 'aam-content',
                _ajax_nonce: aamLocal.nonce,
                subject: this.getSubject().type,
                subjectId: this.getSubject().id
            },
            beforeSend: function () {
                var loader = $('<div/>', {'class': 'aam-loading'}).html(
                        '<i class="icon-spin4 animate-spin"></i>'
                );
                $('#aam-content').html(loader);
            },
            success: function (response) {
                $('#aam-content').html(response);
                //init menu
                initializeMenu();
                //trigger initialization hook
                aam.triggerHook('init');
            }
        });
    };

    aam.fetchContent(); //fetch default AAM content

})(jQuery);