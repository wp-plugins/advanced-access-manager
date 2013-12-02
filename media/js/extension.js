jQuery(document).ready(function() {
    jQuery('#extension_list').dataTable({
        sDom: "<'top'f<'clear'>>t<'footer'p<'clear'>>",
        //bProcessing : false,
        bStateSave: true,
        sPaginationType: "full_numbers",
        bAutoWidth: false,
        bSort: false,
        oLanguage: {
            "sSearch": "",
            "oPaginate": {
                "sFirst": "&Lt;",
                "sLast": "&Gt;",
                "sNext": "&gt;",
                "sPrevious": "&lt;"
            }
        },
        fnDrawCallback: function() {
            jQuery('.extension-action-purchase').each(function() {
                jQuery(this).bind('click', function() {
                    jQuery('form', this).submit();
                });
            });
        }
    });

    initTooltip('#aam');
    
    jQuery('.development').each(function() {
        jQuery(this).bind('click', function() {
            //show the dialog
            jQuery('#under_dev').dialog({
                resizable: false,
                height: 'auto',
                width: '20%',
                modal: true,
                buttons: {
                    Close: function() {
                        jQuery(this).dialog('close');
                    }
                }
            });
        });
    });
});

/**
 * Initialize tooltip for selected area
 *
 * @param {String} selector
 *
 * @returns {void}
 *
 * @access public
 */
function initTooltip(selector) {
    jQuery('[tooltip]', selector).hover(function() {
        // Hover over code
        var title = jQuery(this).attr('tooltip');
        jQuery(this).data('tipText', title).removeAttr('tooltip');
        jQuery('<div/>', {
            'class': 'aam-tooltip'
        }).text(title).appendTo('body').fadeIn('slow');
    }, function() {
        //Hover out code
        jQuery(this).attr('tooltip', jQuery(this).data('tipText'));
        jQuery('.aam-tooltip').remove();
    }).mousemove(function(e) {
        jQuery('.aam-tooltip').css({
            top: e.pageY + 15, //Get Y coordinates
            left: e.pageX + 15 //Get X coordinates
        });
    });
}
;