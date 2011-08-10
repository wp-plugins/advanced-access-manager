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

function mvbam_object(){
    /*
     * Indicates how many changes happend on form
     * 
     * @var int
     * @access private
     */
    this.formChanged = 0;
    
    /*
     * Array of pre-defined capabilities for default WP roles
     * 
     * @var array
     * @access private
     */
    this.roleCapabilities = {
        radio2 : ['moderate_comments', 'manage_categories', 'manage_links', 'upload_files',
        'unfiltered_html', 'edit_posts', 'edit_others_posts', 'edit_published_posts',
        'publish_posts', 'edit_pages', 'read', 'level_7', 'level_6', 'level_5', 'level_4',
        'level_3', 'level_2', 'level_1', 'level_0', 'edit_others_pages', 'edit_published_pages',
        'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages',
        'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts',
        'read_private_posts', 'delete_private_pages', 'edit_private_pages', 'read_private_pages'],
        radio3 : ['upload_files', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 
        'publish_posts', 'read', 'level_2', 'level_1', 'level_0', 'delete_posts', 'delete_published_posts',],
        radio4 : ['edit_posts', 'read', 'level_1', 'level_0', 'delete_posts'],
        radio5 : ['read', 'level_0']
    }
}

/*
 * **
 */
mvbam_object.prototype.addNewRole = function(){
    var newRoleTitle = jQuery.trim(jQuery('#new-role-name').val());
    if (!newRoleTitle){
        jQuery('#new-role-name').effect('highlight',3000);
        return;
    }
    jQuery('#new-role-name').val('');
    jQuery('.new-role-name-empty').show();
    this.showAjaxLoader('#tabs');
    var params = {
        'action' : 'mvbam',
        'sub_action' : 'create_role',
        '_ajax_nonce': wpaccessLocal.nonce,
        'role' : newRoleTitle
    };
    var _this = this;
    jQuery.post(ajaxurl, params, function(data){  
        if (data.result == 'success'){
            var nOption = '<option value="'+data.new_role+'">'+newRoleTitle+'</option>';
            jQuery('#role option:last').after(nOption);
            jQuery('#role').val(data.new_role);
            _this.getRoleOptionList(data.new_role);
            jQuery('div #new-role-form').hide();
            jQuery('.change-role').show();
            //jQuery('#new-role-message-error').hide();
            jQuery('.delete-role-table tbody').append(data.html);
            jQuery('#new-role-message-ok').show().delay(2000).hide('slow');
            
        }else{
            _this.removeAjaxLoader('#tabs');
            //    jQuery('#new-role-message-ok').hide();
            jQuery('#new-role-message-error').show().delay(2000).hide('slow');
        }
    }, 'json'); 
}

mvbam_object.prototype.showAjaxLoader = function(selector){
    jQuery(selector).addClass('loading-new');
    jQuery(selector).prepend('<div class="ajax-loader-big"></div>');
    jQuery('.ajax-loader-big').css({
        top: 150,
        left: jQuery(selector).width()/2 - 25 
    });
}

mvbam_object.prototype.getRoleOptionList = function(currentRoleID){
    this.showAjaxLoader('#tabs');
        
    var params = {
        'action' : 'render_rolelist',
        '_ajax_nonce': wpaccessLocal.nonce,
        'role' : currentRoleID
    };
    jQuery('#current_role').val(currentRoleID);
    var _this = this;
    jQuery.post(wpaccessLocal.handlerURL, params, function(data){
        jQuery('#tabs').tabs('destroy');
        jQuery('#tabs').replaceWith(data.html);
        _this.configureElements();
        jQuery('div #role-select').hide();
        jQuery('#current-role-display').html(jQuery('#role option:selected').text());
        jQuery('.change-role').show();
        //hide or show Restore Default according to server options
        if (data.restorable){
            //sorry to lazy to create my own style :)
            jQuery('#delete-action').show();
        }else{
            jQuery('#delete-action').hide();
        }
    }, 'json');
}

mvbam_object.prototype.configureElements = function(){ 
    this.configureMainMenu();
    this.configureMetaboxes();
    this.configureCapabilities();
}

mvbam_object.prototype.configureMainMenu = function(){
    jQuery( "#tabs" ).tabs(); 
    this.configureAccordion("#main-menu-options");
    jQuery('#main-menu-options > div').each(function(){
        jQuery('#whole', this).bind('click',{
            _this: this
        }, function(event){
            var checked = (jQuery(this).attr('checked') ? true : false);
            jQuery('input:checkbox', event.data._this).attr('checked', checked);
        });
    });
}

mvbam_object.prototype.configureMetaboxes = function(){
    this.configureAccordion('#metabox-list');
    var _this = this;
    jQuery('.initiate-metaboxes').bind('click', function(event){
        jQuery('#initiate-message').hide();
        jQuery('#progressbar').progressbar({
            value: 0
        }).show();
        _this.initiationChain('');
    });
    
    jQuery('.initiate-url').bind('click', function(event){
        var val = jQuery('.initiate-url-text').val();
        if (jQuery.trim(val)){
            jQuery('#initiate-message').hide();
            jQuery('#progressbar').progressbar({
                value: 20
            }).show();
            var params = {
                'action' : 'mvbam',
                'sub_action' : 'initiate_url',
                '_ajax_nonce': wpaccessLocal.nonce,
                'url' : val
            };
            jQuery.post(ajaxurl, params, function(data){
                jQuery('.initiate-url-text').val('');
                jQuery('.initiate-url-empty').show();
                if (data.status == 'success'){
                    jQuery('#progressbar').progressbar('option', 'value', 100);
                    _this.grabInitiatedWM();
                }else{
                    _this.emergencyCall(data);
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
}

mvbam_object.prototype.configureCapabilities = function(){
    jQuery(".capability-item span").each(function(){
        if(jQuery(this).attr('title')){
            jQuery(this).tooltip({
                position : 'center right'
            });
        }else{
            jQuery(this).remove();
        }
    });
    
    jQuery('#radio-list').buttonset();
}

mvbam_object.prototype.emergencyCall = function(data){
    var _this = this;
    jQuery.ajax(data.url, {
        success : function(){ 
            if (data.next){
                _this.initiationChain(data.next);
            }else{
                jQuery('#progressbar').progressbar("option", "value", 100);
                _this.grabInitiatedWM();
            }
        },
        error : function(jqXHR, textStatus, errorThrown){
            if (textStatus != 'timeout'){
                if (data.next){
                    _this.initiationChain(data.next);
                }else{
                    jQuery('#progressbar').progressbar("option", "value", 100);
                    _this.grabInitiatedWM();
                }
            }else{
                alert('Error Appears during Metabox initialization!');
                jQuery('#progressbar').progressbar("option", "value", 100);
                _this.grabInitiatedWM();
            }
        },
        timeout : 5000
    });

}

mvbam_object.prototype.initiationChain = function(next){
    //start initiation
    var params = {
        'action' : 'mvbam',
        'sub_action' : 'initiate_wm',
        '_ajax_nonce': wpaccessLocal.nonce,
        'next' : next
    };
    var _this = this;
    jQuery.post(ajaxurl, params, function(data){
        jQuery('#progressbar').progressbar("option", "value", data.value);
        if (data.status == 'success'){
            if (data.next){
                _this.initiationChain(data.next);
            }else{
                jQuery('#progressbar').progressbar("option", "value", 100);
                _this.grabInitiatedWM();
            }
        }else{
            //try directly to go to that page
            _this.emergencyCall(data);
        }
    }, 'json');
}

mvbam_object.prototype.grabInitiatedWM = function(){
    jQuery('#progressbar').progressbar('destroy').hide();
    jQuery('#initiate-message').show();
    jQuery('#metabox-list').empty().css({
        'height' : '200px',
        'width' : '100%'
    });
    this.showAjaxLoader('#metabox-list');
    var params = {
        'action' : 'mvbam',
        'sub_action' : 'render_metabox_list',
        '_ajax_nonce': wpaccessLocal.nonce,
        'role' : jQuery('#current_role').val()
    };
    var _this = this;    
    jQuery.post(ajaxurl, params, function(data){
        jQuery('#metabox-list').replaceWith(data);
        _this.configureAccordion('#metabox-list');
    });
}

mvbam_object.prototype.configureAccordion = function(selector){
     var icons = {
        header: "ui-icon-circle-arrow-e",
        headerSelected: "ui-icon-circle-arrow-s"
    };

    jQuery(selector).accordion({
        collapsible: true,
        header: 'h4',
        autoHeight: false,
        icons: icons,
        active: -1
    });
}

mvbam_object.prototype.deleteRole = function(role){
    jQuery('#delete-role-title').html(jQuery('.delete-role-table #dl-row-'+role+' td:first').html());
    var _this = this;
    jQuery( "#dialog-delete-confirm" ).dialog({
        resizable: false,
        height:180,
        modal: true,
        buttons: {
            "Delete Role": function() {
                var params = {
                    'action' : 'mvbam',
                    'sub_action' : 'delete_role',
                    '_ajax_nonce': wpaccessLocal.nonce,
                    'role' : role
                };
                jQuery.post(ajaxurl, params, function(){ 
                    jQuery('.delete-role-table #dl-row-' + role).remove();
                    if (jQuery('#role option[value="'+role+'"]').attr('selected')){
                        _this.getRoleOptionList(jQuery('#role option:first').val());
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

mvbam_object.prototype.changeCapabilities = function(type){
    switch(type){
        case 'radio1': //administrator
            jQuery('.capability-item input').attr('checked', true);
            break;
        
        case 'radio2': //editor
        case 'radio3': //author
        case 'radio4': //contributor
        case 'radio5': //subscriber
            jQuery('.capability-item input').attr('checked', false);
            for (var c in this.roleCapabilities[type]){
                jQuery('.capability-item input[name*="['+this.roleCapabilities[type][c]+']"]').attr('checked', true);
            }
            break;
        
        case 'radio6': //clear all
            jQuery('.capability-item input').attr('checked', false);
            break;
            
        default:
            break;
    }
}

mvbam_object.prototype.changeRole = function(){
    var currentRoleID = jQuery('#role').val();
    this.getRoleOptionList(currentRoleID); 
    this.formChanged = 0;
}

mvbam_object.prototype.submitForm = function(){
    this.formChanged = -1;
    jQuery('#ajax-loading').show();
}

mvbam_object.prototype.goodbye = function(e){
    if (this.formChanged > 0){
        if(!e) e = window.event;
        //e.cancelBubble is supported by IE - this will kill the bubbling process.
        e.cancelBubble = true;
        e.returnValue = 'You sure you want to leave?'; //This is displayed on the dialog

        //e.stopPropagation works in Firefox.
        if (e.stopPropagation) {
            e.stopPropagation();
            e.preventDefault();
        }
    }
}

mvbam_object.prototype.restoreDefault = function(){
    var _this = this;
    jQuery( "#dialog-confirm" ).dialog({
        resizable: false,
        height:180,
        modal: true,
        buttons: {
            "Restore": function() {
                var role = jQuery('#current_role').val();
                var params = {
                    'action' : 'mvbam',
                    'sub_action' : 'restore_role',
                    '_ajax_nonce': wpaccessLocal.nonce,
                    'role' : role
                };
                var _dialog = this;
                jQuery.post(ajaxurl, params, function(data){
                    if (data.status == 'success'){
                        _this.getRoleOptionList(role);
                    }else{
                        //TODO - Implement error
                        alert('Current Role can not be restored!');
                    }
                    jQuery( _dialog ).dialog( "close" );
                },'json');
            },
            Cancel: function() {
                jQuery( this ).dialog( "close" );
            }
        }
    });
}

mvbam_object.prototype.removeAjaxLoader = function(selector){
    jQuery(selector).removeClass('loading-new');
    jQuery('.ajax-loader-big').remove();
}
//**********************************************

jQuery(document).ready(function(){
    mObj = new mvbam_object();
    
    mObj.configureElements();
    jQuery('.change-role').bind('click', function(e){
        e.preventDefault();
        jQuery('div #role-select').show();
        jQuery(this).hide();
    });
    
    jQuery('#role-ok').bind('click', function(e){
        e.preventDefault();
        if (mObj.formChanged > 0){
            jQuery( "#leave-confirm" ).dialog({
                resizable: false,
                height: 180,
                modal: true,
                buttons: {
                    "Change Role": function() {
                        jQuery( this ).dialog( "close" );
                        mObj.changeRole();
                    },
                    Cancel: function() {
                        jQuery( this ).dialog( "close" );
                    }
                }
            }); 
        }else{
            mObj.changeRole();
        }
       
    });
    
    
    jQuery('.new-role-name-empty').click(function(e){
        e.preventDefault();
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
    jQuery('#new-role-name').keypress(function(event){
        if (event.keyCode == 13){
            event.preventDefault();
            mObj.addNewRole();
        };
    });
    
    jQuery('#wp-access').keypress(function(event){
        if (event.keyCode == 13){
            event.preventDefault();
        };
    });
   
    jQuery('#new-role-ok').bind('click', function(e){
        e.preventDefault();
        mObj.addNewRole();
    });
    
    jQuery('#role-cancel').bind('click', function(e){
        e.preventDefault();
        jQuery('div #role-select').hide();
        jQuery('.change-role').show();
    });
    jQuery('#new-role-cancel').bind('click', function(e){
        e.preventDefault();
        jQuery('div #new-role-form').hide();
        jQuery('.change-role').show();
    });
    
    jQuery('#role-tabs').tabs();
    
    jQuery('.deletion').bind('click', function(e){
        e.preventDefault();
        mObj.restoreDefault();
    });
    
    jQuery('#wp-access').bind('change', function(e){
        e.preventDefault();
        mObj.formChanged++; 
    });
    
    jQuery('#role').bind('change', function(e){
        e.preventDefault();
        mObj.formChanged -= 1;
    });

    jQuery('.message-active').show().delay(5000).hide('slow');
    
    //window.onbeforeunload = mObj.goodbye;
    
    jQuery('#wp-access').show();
    
});