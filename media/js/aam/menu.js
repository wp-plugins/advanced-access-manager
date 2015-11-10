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
    function initialize() {
        $('.aam-restrict-menu').each(function () {
            $(this).bind('click', function () {
                var status = ($('i', $(this)).hasClass('icon-eye-off') ? 1 : 0);
                var target = $(this).data('target');

                $('i', $(this)).attr('class', 'icon-spin4 animate-spin');

                var result = aam.save($(this).data('menu-id'), status, 'menu');
                
                if (result.status === 'success') {
                    if (status === 1) { //locked the menu
                        $('input', target).each(function () {
                            $(this).attr('checked', true);
                            aam.save($(this).data('menu-id'), status, 'menu');
                        });
                        $('.aam-bordered', target).append(
                                $('<div/>', {'class': 'aam-lock'})
                        );
                        $(this).removeClass('btn-danger').addClass('btn-primary');
                        $(this).html(
                            '<i class="icon-eye"></i>' + aam.__('Show Menu')
                        );
                        //add menu restricted indicator
                        var ind = $('<i/>', {
                            'class' : 'aam-panel-title-icon icon-eye-off text-danger'
                        });
                        $('.panel-title', target + '-heading').append(ind);
                    } else {
                        $('input', target).each(function () {
                            $(this).attr('checked', false);
                            aam.save($(this).data('menu-id'), status, 'menu');
                        });
                        $('.aam-lock', target).remove();
                        $(this).removeClass('btn-primary').addClass('btn-danger');
                        $(this).html(
                            '<i class="icon-eye-off"></i>' + aam.__('Hide Menu')
                        );
                        $('.panel-title .icon-eye-off', target + '-heading').remove();
                    }
                }
            });
        });

        $('input[type="checkbox"]', '#admin-menu').each(function () {
            $(this).bind('click', function () {
                aam.save(
                    $(this).data('menu-id'), !$(this).attr('checked'), 'menu'
                );
            });
        });
    }

    aam.addHook('init', initialize);

})(jQuery);