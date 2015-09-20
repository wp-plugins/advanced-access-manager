/**
    Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

    This program is commercial software: you are not allowed to redistribute it 
    and/or modify. Unauthorized copying of this file, via any medium is strictly 
    prohibited.
    For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

jQuery(document).ready(function() {
    aamInterface.addAction('aam_get_access_loaded', function() {
        //remove notification
        jQuery('.attachment-access-block', '#access_dialog').remove();
    });
});