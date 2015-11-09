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
        $('#manage-visitor').bind('click', function (event) {
            event.preventDefault();
            aam.setSubject('visitor', null);
            $('i.icon-cog', $(this)).attr('class', 'icon-spin4 animate-spin');
            aam.fetchContent();
            $('i.icon-spin4', $(this)).attr('class', 'icon-cog');
        });
    }

    //add setSubject hook
    aam.addHook('init', initialize);

})(jQuery);