/*
  Copyright (C) <2011>  Vasyl Martyniuk <martyniuk.vasyl@gmail.com>

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

jQuery(document).ready(function(){
    configureElements();
    jQuery('.change-role').bind('click', function(){
        jQuery('div #role-select').show();
        jQuery(this).hide();
    });
    
    jQuery('#role-ok').bind('click', function(){
        var currentRoleID = jQuery('#role').val();
        getRoleOptionList(currentRoleID);
    });
    
    
    jQuery('.new-role-name-empty').click(function(){
        jQuery('#new-role-name').focus();
    });
    jQuery('#new-role-name').focus(function(){
        jQuery('.new-role-name-empty').hide();
    });
    jQuery('#new-role-name').blur(function(){
        if (!jQuery.trim(jQuery(this).val())){
            jQuery('.new-role-name-empty').show();
        }
    });
   
    jQuery('#new-role-ok').bind('click', function(){
        var newRoleTitle = jQuery.trim(jQuery('#new-role-name').val());
        if (!newRoleTitle){
            jQuery('#new-role-name').effect('highlight',3000);
            return;
        }
        jQuery('#new-role-name').val('');
        jQuery('.new-role-name-empty').show();
        showAjaxLoader('#tabs');
        var params = {
            'action' : 'create_newRole',
            '_ajax_nonce': wpaccessLocal.nonce,
            'role' : newRoleTitle
        };
        
        jQuery.post(ajaxurl, params, function(data){  
 
            if (data.result == 'success'){
                var nOption = '<option value="'+data.new_role+'">'+newRoleTitle+'</option>';
                jQuery('#role option:last').after(nOption);
                jQuery('#role').val(data.new_role);
                getRoleOptionList(data.new_role);
                jQuery('div #new-role-form').hide();
                jQuery('.change-role').show();
                //jQuery('#new-role-message-error').hide();
                jQuery('.delete-role-table tbody').append(data.html);
                jQuery('#new-role-message-ok').show().delay(2000).hide('slow');
            }else{
                removeAjaxLoader('#tabs');
                //    jQuery('#new-role-message-ok').hide();
                jQuery('#new-role-message-error').show().delay(2000).hide('slow');
            }
        }, 'json');
    });
    
    jQuery('#role-cancel').bind('click', function(){
        jQuery('div #role-select').hide();
        jQuery('.change-role').show();
    });
    jQuery('#new-role-cancel').bind('click', function(){
        jQuery('div #new-role-form').hide();
        jQuery('.change-role').show();
    });
    
    jQuery('#role-tabs').tabs();
    
});

function initiationChain(next){
    //start initiation
    var params = {
        'action' : 'initiateWM',
        '_ajax_nonce': wpaccessLocal.nonce,
        'next' : next
    };
    jQuery.post(wpaccessLocal.handlerURL, params, function(data){ 
        //alert(data);
        if (data.result == 'success'){
            jQuery('#progressbar').progressbar("option", "value", data.value);
        }
        if (data.next){
            initiationChain(data.next);
        }else{
            jQuery('#progressbar').progressbar("option", "value", 100);
            grabInitiatedWM();
        }
    }, 'json');
}

function grabInitiatedWM(){
    jQuery('#progressbar').progressbar('destroy').hide();
    jQuery('#initiate-message').show();
    jQuery('#metabox-list').empty().css({
        'height' : '200px',
        'width' : '100%'
    });
    showAjaxLoader('#metabox-list');
    var params = {
        'action' : 'render_metaboxList',
        '_ajax_nonce': wpaccessLocal.nonce,
        'role' : jQuery('#current_role').val()
    };
        
    jQuery.post(ajaxurl, params, function(data){
        removeAjaxLoader('#metabox-list');
        jQuery('#metabox-list').css('height', 'auto');
        jQuery('#metabox-list').html(data);
    });
}

function getRoleOptionList(currentRoleID){
    showAjaxLoader('#tabs');
        
    var params = {
        'action' : 'render_rolelist',
        '_ajax_nonce': wpaccessLocal.nonce,
        'role' : currentRoleID
    };
    jQuery('#current_role').val(currentRoleID);

    jQuery.post(wpaccessLocal.handlerURL, params, function(data){
        jQuery('#tabs').tabs('destroy');
        jQuery('#tabs').replaceWith(data);
        configureElements();
        jQuery('div #role-select').hide();
        jQuery('#current-role-display').html(jQuery('#role option:selected').text());
        jQuery('.change-role').show();
    });
}

function showAjaxLoader(selector){
    jQuery(selector).addClass('loading-new');
    jQuery(selector).prepend('<div class="ajax-loader-big"></div>');
    jQuery('.ajax-loader-big').css({
        top: 150,
        left: jQuery(selector).width()/2 - 25 
    });
}

function removeAjaxLoader(selector){
    jQuery(selector).removeClass('loading-new');
    jQuery('.ajax-loader-big').remove();
}

function deleteRole(role){ 
    jQuery('#delete-role-title').html(jQuery('.delete-role-table #dl-row-'+role+' td:first').html());
    jQuery( "#dialog-delete-confirm" ).dialog({
        resizable: false,
        height:160,
        modal: true,
        buttons: {
            "Delete Role": function() {
                var params = {
                    'action' : 'delete_newRole',
                    '_ajax_nonce': wpaccessLocal.nonce,
                    'role' : role
                };
                jQuery.post(ajaxurl, params, function(){ 
                    jQuery('.delete-role-table #dl-row-' + role).remove();
                    if (jQuery('#role option[value="'+role+'"]').attr('selected')){
                        getRoleOptionList(jQuery('#role option:first').val());
                    }
                    jQuery('#role option[value="'+role+'"]').remove();
                });
                jQuery( this ).dialog( "close" );
            },
            Cancel: function() {
                jQuery( this ).dialog( "close" );
            }
        }
    });
}

function configureElements(){
    jQuery( "#tabs" ).tabs(); 
    var icons = {
        header: "ui-icon-circle-arrow-e",
        headerSelected: "ui-icon-circle-arrow-s"
    };
    jQuery("#main-menu-options").accordion({
        collapsible: true,
        header: 'h4',
        autoHeight: false,
        icons: icons,
        active: -1
    });
    
    jQuery('#main-menu-options > div').each(function(){
        var id = jQuery(this).attr('id');
        jQuery('#main-menu-options #'+id+' #whole').bind('click',{
            id: id
        }, function(){
            jQuery('#main-menu-options #'+id+' input:checkbox').attr('checked', jQuery(this).attr('checked'));
        });
    });
    
    jQuery(".capability-item label").each(function(){
        if(jQuery(this).attr('title')){
            jQuery(this).tooltip();
        }
    });
    
    jQuery('.initiate-metaboxes').bind('click', function(){
           
        jQuery('#initiate-message').hide();
        jQuery('#progressbar').progressbar({
            value: 0
        }).show();
        initiationChain('');
    });
    
    jQuery('.initiate-url').bind('click', function(){
        var val = jQuery('.initiate-url-text').val();
        if (jQuery.trim(val)){
            jQuery('#initiate-message').hide();
            jQuery('#progressbar').progressbar({
                value: 20
            }).show();
            var params = {
                'action' : 'initiateURL',
                '_ajax_nonce': wpaccessLocal.nonce,
                'url' : val
            };
            jQuery.post(wpaccessLocal.handlerURL, params, function(data){
                if (data.result == 'success'){
                    jQuery('.initiate-url-text').val('');
                    jQuery('.initiate-url-empty').show();
                    jQuery('#progressbar').progressbar('option', 'value', 100);
                    grabInitiatedWM();
                }
            }, 'json');
        }else{
            jQuery('.initiate-url-text').effect('highlight',3000);
        }
    });
    
    jQuery('.initiate-url-empty').click(function(){
        jQuery('#initiateURL').focus();
    });
    jQuery('#initiateURL').focus(function(){
        jQuery('.initiate-url-empty').hide();
    });
    jQuery('#initiateURL').blur(function(){
        if (!jQuery.trim(jQuery(this).val())){
            jQuery('.initiate-url-empty').show();
        }
    });

    
    jQuery('#tabs').css('visibility', 'visible');
}