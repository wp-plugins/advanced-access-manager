/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 * Add Feature Example funciton to AAM prototype object
 * 
 * @returns void
 */
AAM.prototype.activatePluginManagerTab = function() {
    var _this = this;

    if (typeof this.blogTables.pluginManagerList === 'undefined'
            || this.blogTables.pluginManagerList === null) {
        this.blogTables.pluginManagerList = jQuery('#plugin_list').dataTable({
            sDom: "t<'footer'ip<'clear'>>",
            sPaginationType: "full_numbers",
            bAutoWidth: false,
            bDestroy: true,
            bSort: false,
            fnRowCallback: function(nRow, aData, order) {
                //create hide checkbox
                jQuery('td:eq(1)', nRow).empty().append(
                        createCheckbox(nRow, 3, order, 'hide', parseInt(aData[3]))
                        );
                //create activate checkbox
                jQuery('td:eq(2)', nRow).empty().append(
                        createCheckbox(nRow, 5, order, 'activate', parseInt(aData[5]))
                        );
                //create deactivate checkbox
                jQuery('td:eq(3)', nRow).empty().append(
                        createCheckbox(nRow, 7, order, 'deactivate', parseInt(aData[7]))
                        );
                //create delete checkbox
                jQuery('td:eq(4)', nRow).empty().append(
                        createCheckbox(nRow, 9, order, 'delete', parseInt(aData[9]))
                        );
            },
            aoColumnDefs: [{
                    bVisible: false,
                    aTargets: [0, 3, 5, 7, 9]
                }],
            oLanguage: {
                oPaginate: {
                    sFirst: "&Lt;",
                    sLast: "&Gt;",
                    sNext: "&gt;",
                    sPrevious: "&lt;"
                }
            }
        });
    }

    function createCheckbox(nRow, nCol, order, name, checked) {
        var holder = jQuery('<div/>');
        holder.append(jQuery('<input/>', {
            type: 'checkbox',
            id: 'plugin_' + order + '_' + name
        }).bind('change', function() {
            var status = (jQuery(this).prop('checked') === true ? 1 : 0);
            _this.blogTables.pluginManagerList.fnUpdate(status, nRow, nCol, false);
        }).prop('checked', checked));
        holder.append(jQuery('<label/>', {
            'for': 'plugin_' + order + '_' + name
        }).html(jQuery('<span/>')));

        return holder;
    }
};

jQuery(document).ready(function() {
    aamInterface.addAction('aam_feature_activation', function(params) {
        if (params.feature === 'plugin_manager') {
            aamInterface.activatePluginManagerTab();
        }
    });
    aamInterface.addAction('aam_before_save', function(data) {
        if (typeof this.blogTables.pluginManagerList !== 'undefined'
                && this.blogTables.pluginManagerList !== null) {
            if (jQuery('#plugin_list').length) {
                var caps = this.blogTables.pluginManagerList.fnGetData();
                for (var i in caps) {
                    data['aam[plugin][' + caps[i][0] + '][hide]'] = caps[i][3];
                    data['aam[plugin][' + caps[i][0] + '][activate]'] = caps[i][5];
                    data['aam[plugin][' + caps[i][0] + '][deactivate]'] = caps[i][7];
                    data['aam[plugin][' + caps[i][0] + '][delete]'] = caps[i][9];
                }
            }
        }
    });
});