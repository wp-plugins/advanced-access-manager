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
     * Indicate that we are soring Main Menu
     * 
     * @var bool
     * @access private
     */
    this.sorting = false;
    
    /*
     *Indicate that sorting has been present
     *
     *@var bool
     *@access private
     */
    this.sorted = false;
    
    /*
     * Continue submition
     * 
     * @var bool
     * @access private
     */
    this.submiting = false;
    
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

mvbam_object.prototype.showAjaxLoader = function(selector, type){
    jQuery(selector).addClass('loading-new');
    var l_class = (type == 'small' ? 'ajax-loader-small' : 'ajax-loader-big');
    
    jQuery(selector).prepend('<div class="' + l_class +'"></div>');
    jQuery('.' + l_class).css({
        top: jQuery(selector).height()/2 - (type == 'small' ? 16 : 50),
        left: jQuery(selector).width()/2 - (type == 'small' ? 8 : 25) 
    });
}

mvbam_object.prototype.removeAjaxLoader = function(selector, type){
    jQuery(selector).removeClass('loading-new');
    var l_class = (type == 'small' ? 'ajax-loader-small' : 'ajax-loader-big');
    jQuery('.' + l_class).remove();
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
    //restore some params
    this.sorting = false;
    this.sorted = false;
    
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
        }
        else{
            jQuery('#delete-action').hide();
        }
    }, 'json');
}

mvbam_object.prototype.configureElements = function(){ 
    this.configureMainMenu();
    this.configureMetaboxes();
    this.configureCapabilities();
    this.postPage();
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
    var _this = this;
    //add reorganize menu functionality
    //jQuery('#reorganize').button();
    jQuery('#reorganize').bind('click', function(event){
        event.preventDefault();
        if (_this.sorting){
            jQuery('#sorting-tip').hide();
            //jQuery('#reorganize').button('option', 'label', 'Reorganize');
            jQuery('#reorganize span').html('Reorganize');
            //save confirmation message
            if (_this.sorted){
                jQuery( "#dialog-reorder-confirm #role-title" ).html(jQuery('#current-role-display').html());
                jQuery( "#dialog-reorder-confirm" ).dialog({
                    resizable: false,
                    height:180,
                    modal: true,
                    buttons: {
                        "Yes": function() {
                            _this.saveMenuOrder(false);
                            jQuery( this ).dialog( "close" );
                        },
                        "Apply for All": function() {
                            _this.saveMenuOrder(true);
                            jQuery( this ).dialog( "close" );
                        }
                    }
                });
            }
            _this.configureAccordion('#main-menu-options');
        }else{
            jQuery('#sorting-tip').show();
            //jQuery('#reorganize').button('option', 'label', 'Save Order');
            jQuery('#reorganize span').html('Save Order');
            _this.configureAccordion('#main-menu-options', true);
        }
        _this.sorting = !_this.sorting;
    });
    
    //check Add-ons
    var params = {
        action: "mvbam",
        sub_action : 'check_addons',
        '_ajax_nonce': wpaccessLocal.nonce
    }
    jQuery.post(ajaxurl, params, function(data){
        if (data.status == 'success' && data.available){
            jQuery('.addons-info').removeClass('dialog');
            jQuery('.addons-info').bind('click', function(event){
                event.preventDefault();
                jQuery( "#addons-notice" ).dialog({
                    resizable: false,
                    height:190,
                    modal: true,
                    buttons: {
                        OK: function() {
                            jQuery( this ).dialog( "close" );
                        }
                    }
                });
            });
        } 
    }, 'json');   
}

mvbam_object.prototype.saveMenuOrder = function(apply_all){
    this.sorted = false;
    this.sorting = false;
    var _this = this;
    var params = {
        'action' : 'mvbam',
        'sub_action' : 'save_order',
        '_ajax_nonce': wpaccessLocal.nonce,
        'apply_all' : (apply_all ? 1 : 0),
        'role' : jQuery('#role').val(),
        'menu' : new Array()
    }
    //get list of menus in proper order
    jQuery('#main-menu-options > div').each(function(){
        params.menu.push(jQuery(this).attr('id'));
    });
    
    jQuery.post(ajaxurl, params, function(data){
        if (data.status == 'success'){
            if (_this.submiting){
                jQuery('#wp-access').submit();
            }
        }
    }, 'json');
}

mvbam_object.prototype.configureMetaboxes = function(){
    this.configureAccordion('#metabox-list');
    var _this = this;
    jQuery('.initiate-metaboxes').bind('click', function(event){
        event.preventDefault();
        jQuery('#initiate-message').hide();
        jQuery('#progressbar').progressbar({
            value: 0
        }).show();
        _this.initiationChain('');
    });
    
    jQuery('.initiate-url').bind('click', function(event){
        event.preventDefault();
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
        event.preventDefault();
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
    var _this = this;
    jQuery(".capability-description").each(function(){
        if(jQuery(this).attr('title')){
            jQuery(this).tooltip({
                position : 'center right',
                events : {
                    def:     "click,mouseout",
                    input:   "focus,blur",
                    widget:  "focus mouseover,blur mouseout",
                    tooltip: "mouseover,mouseout"
                }
            });
        }else{
            jQuery(this).remove();
        }
    });
    
    jQuery('.default-roles > a').each(function(){
        var id = jQuery(this).attr('id');
        jQuery(this).bind('click', function(event){
            _this.changeCapabilities(event, id);
        })
    });
    jQuery('#new-capability').bind('click', function(e){
        e.preventDefault();
        _this.addNewCapability();
    });
}

mvbam_object.prototype.postPage = function(){
    jQuery("#tree").treeview({
        url: ajaxurl,
        // add some additional, dynamic data and request with POST
        ajax: {
            data : {
                action: "mvbam",
                sub_action : 'get_treeview',
                '_ajax_nonce': wpaccessLocal.nonce
            },
            type : 'post'
        },
        animated: "medium",
        control:"#sidetreecontrol",
        persist: "location"
    });        
}

mvbam_object.prototype.addNewCapability = function(){
    jQuery( "#capability-form #new-cap" ).val('').focus();
    jQuery( "#capability-form" ).dialog({
        resizable: false,
        height:150,
        modal: false,
        buttons: {
            "Add Capability": function() {
                var cap = jQuery( "#capability-form #new-cap" ).val();
                
                if (jQuery.trim(cap)){
                    jQuery( this ).dialog( "close" );
                    var params = {
                        'action' : 'mvbam',
                        'sub_action' : 'add_capability',
                        '_ajax_nonce': wpaccessLocal.nonce,
                        'cap' : cap,
                        'role' : jQuery('#role').val()
                    };
                    jQuery.post(ajaxurl, params, function(data){
                        if (data.status == 'success'){
                            jQuery('.capability-item:last').after(data.html);
                            jQuery('.capability-item:last .info').remove();
                        }else{
                            alert(data.message);
                        }
                    }, 'json');
                }else{
                    jQuery( "#capability-form #new-cap" ).effect('highlight', 5000);
                }
            },
            Cancel: function() {
                jQuery( this ).dialog( "close" );
            }
        }
    });
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

mvbam_object.prototype.configureAccordion = function(selector, sortable){
    
    var icons = {
        header: "ui-icon-circle-arrow-e",
        headerSelected: "ui-icon-circle-arrow-s"
    };
    var _this = this;
    jQuery(selector).accordion('destroy');
    if (sortable === true){
        jQuery(selector).accordion({
            collapsible: true,
            header: 'h4',
            autoHeight: false,
            icons: icons,
            active: -1
        }).sortable({
            axis: "y",
            handle: "h4",
            stop: function() {
                stop = true;
                _this.sorted = true;
            }
        });
    }else{
        jQuery(selector).sortable('destroy');
        jQuery(selector).accordion({
            collapsible: true,
            header: 'h4',
            autoHeight: false,
            icons: icons,
            active: -1
        });
    }
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

mvbam_object.prototype.changeCapabilities = function(event, type){
    
    event.preventDefault();
    
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
    var result = true;
    //check if user still reorganizing menu
    if (this.sorting && this.sorted){
        this.submiting = true;
        this.saveMenuOrder(false); //apply only for one role
        result = false; //wait until ordering saves
    }
    
    return result;
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

mvbam_object.prototype.loadInfo = function(event, type, id){
    
    if (typeof(event.preventDefault) != 'undefined'){ //for IE
        event.preventDefault();
    }else{
        event.returnValue = false;
    }
    this.showAjaxLoader('.post-information', 'small');
    var _this = this;
    var params = {
        'action' : 'mvbam',
        'sub_action' : 'get_info',
        '_ajax_nonce': wpaccessLocal.nonce,
        'type' : type,
        'role' : jQuery('#role').val(),
        'id' : id
    }
    
    jQuery.post(ajaxurl, params, function(data){
        _this.removeAjaxLoader('.post-information', 'small');
        if (data.status == 'success'){
            var pi = jQuery('.post-information');
            jQuery('#post-date', pi).html(data.html);
            
            //stop bothering user that detected form changes
            jQuery('input', pi).bind('change', function(){
                _this.formChanged--;
            });
             
            //configure information
            jQuery('#restrict_expire', pi).datepicker({
                'minDate' : new Date()
            });
            jQuery('.info > div', pi).tooltip({
                position : 'center right',
                events : {
                    def:     "click,mouseout",
                    input:   "focus,blur",
                    widget:  "focus mouseover,blur mouseout",
                    tooltip: "mouseover,mouseout"
                }
            });
            jQuery('.save-postinfo', pi).bind('click', function(event){
                _this.showAjaxLoader('.post-information', 'small');
                event.preventDefault(); 
                //save information
                var params = {
                    'action' : 'mvbam',
                    'sub_action' : 'save_info',
                    '_ajax_nonce': wpaccessLocal.nonce,
                    'type' : type,
                    'role' : jQuery('#role').val(),
                    'restrict' : jQuery('input[name="restrict_access"]', pi).attr('checked'),
                    'restrict_expire' : jQuery('#restrict_expire', pi).val(),
                    'id' : id
                }
                jQuery.post(ajaxurl, params, function(data){
                    _this.removeAjaxLoader('.post-information', 'small');
                    if (data.status == 'success'){
                        jQuery('#expire-success-message', pi).show().delay(5000).hide('slow');
                    }else{
                        jQuery('#expire-error-message', pi).show().delay(10000).hide('slow');
                        if (data.message){
                            jQuery( "#addon-proposal #addon-message" ).html(data.message);
                            jQuery( "#addon-proposal" ).dialog({
                                resizable: false,
                                height:190,
                                modal: true,
                                buttons: {
                                    OK: function() {
                                        jQuery( this ).dialog( "close" );
                                    }
                                }
                            });
                        }
                    }
                }, 'json');
            });
        }else{
            //TODO - Implement error
            alert('Error during information grabbing!');
        }
    },'json');
}

mvbam_object.prototype.handleError = function(err){
    
    jQuery('#error-message #error-text').html(err.toString());
    jQuery('#error-message').removeClass('message-passive');
}

mvbam_object.prototype.deleteCapability = function(cap, label){
    jQuery('#delete-capability-title').html(label);
    var _this = this;
    jQuery( "#dialog-delete-capability" ).dialog({
        resizable: false,
        height:180,
        modal: true,
        buttons: {
            "Delete Capability": function() {
                var params = {
                    'action' : 'mvbam',
                    'sub_action' : 'delete_capability',
                    '_ajax_nonce': wpaccessLocal.nonce,
                    'cap' : cap
                };
                jQuery.post(ajaxurl, params, function(data){ 
                    if (data.status == 'success'){
                        jQuery('#cap-' + cap).parent().parent().remove();
                    }else{
                        alert(data.message);
                    }
                }, 'json');
                jQuery( this ).dialog( "close" );
            },
            Cancel: function() {
                jQuery( this ).dialog( "close" );
            }
        }
    });
}

//**********************************************

jQuery(document).ready(function(){
    
    try{
        mObj = new mvbam_object();
    
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
            if (event.which == 13){
                event.preventDefault();
                mObj.addNewRole();
            };
        });
    
        jQuery('#wp-access').keypress(function(event){
            if (event.which == 13){
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
            mObj.formChanged++; 
        });
        jQuery('#role').bind('change', function(e){
            mObj.formChanged -= 1;
        });

        jQuery('.message-active').show().delay(5000).hide('slow');
    
        //window.onbeforeunload = mObj.goodbye;
        mObj.configureElements();

        jQuery('#wp-access').show();
    }catch(err){
        mObj.handleError(err);
        jQuery('#wp-access').show();
    }
});

function loadInfo(event, type, id){
    mObj.loadInfo(event, type, id);
}