/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

(function ($) {

    var dump = null;

    /**
     * 
     * @param {type} data
     * @returns {undefined}
     */
    function downloadExtension(data) {
        $.ajax(aamLocal.ajaxurl, {
            type: 'POST',
            dataType: 'json',
            async: false,
            data: data,
            success: function (response) {
                if (response.status === 'success') {
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    aam.notification('danger', aam.__(response.error));
                    if (typeof response.content !== 'undefined') {
                        dump = response;
                        $('#installation-error').text(response.error);
                        $('#extension-notification-modal').modal('show');
                    }
                }
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

        //check if extension list is empty
        if ($('#extension-list tbody tr').length === 1) {
            $('#extension-list-empty').removeClass('hidden');
        }

        //init refresh list button
        $('#install-extension').bind('click', function (event) {
            event.preventDefault();

            $('#extension-key').parent().removeClass('error');
            var license = $.trim($('#extension-key').val());

            if (!license) {
                $('#extension-key').parent().addClass('error');
                $('#extension-key').focus();
                return;
            }

            $('i', $(this)).attr('class', 'icon-spin4 animate-spin');
            downloadExtension({
                action: 'aam',
                sub_action: 'Extension.install',
                _ajax_nonce: aamLocal.nonce,
                license: $('#extension-key').val()
            });
            $('i', $(this)).attr('class', 'icon-download-cloud');
        });

        //update extension
        $('.aam-update-extension').each(function () {
            $(this).bind('click', function (event) {
                event.preventDefault();

                $('i', $(this)).attr('class', 'icon-spin4 animate-spin');
                downloadExtension({
                    action: 'aam',
                    sub_action: 'Extension.update',
                    _ajax_nonce: aamLocal.nonce,
                    extension: $(this).data('product')
                });
                $('i', $(this)).attr('class', 'icon-arrows-cw');
            });
        });
        
        //download extension
        $('.aam-download-extension').each(function () {
            $(this).bind('click', function (event) {
                event.preventDefault();

                $('i', $(this)).attr('class', 'icon-spin4 animate-spin');
                downloadExtension({
                    action: 'aam',
                    sub_action: 'Extension.install',
                    _ajax_nonce: aamLocal.nonce,
                    license: $(this).data('license')
                });
                $('i', $(this)).attr('class', 'icon-download-cloud');
            });
        });

        //bind the download handler
        $('#download-extension').bind('click', function () {
            download(
                    'data:image/gif;base64,' + dump.content,
                    dump.title + '.zip',
                    'application/zip'
                    );
            $('#extension-notification-modal').hide();
        });
    }

    aam.addHook('init', initialize);

})(jQuery);