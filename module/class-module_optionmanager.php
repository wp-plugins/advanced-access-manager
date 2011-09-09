<?php

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

class module_optionManager extends mvb_corePlugin {
    /*
     * Indicate if return rendered HTML or
     * print it
     * 
     * @var bool
     * @access private
     */

    private $return;

    /*
     * Template object holder
     * 
     * @var object <mvb_coreTemplate>
     * @access private
     */
    private $templObj;

    /*
     * HTML templated from file
     * 
     * @var string Template to work with
     * @access private
     */
    private $template;

    /*
     * Array of User Roles
     * 
     * @var array
     * @access private
     */
    private $roles;

    /*
     * Current role to work with
     * 
     * @var string
     * @access private
     */
    private $currentRole;

    /*
     * Current plugin's params from option database table
     * 
     * @var array
     * @access private 
     */
    private $currentParams;

    /*
     * Initiate an object and other parameters
     * 
     * @param string Current role to work with
     * @param bool If TRUE, then return rendered HTML to the caller
     */

    function __construct($curentRole = FALSE, $return = FALSE) {
        global $wpdb;

        $this->return = $return;
        $this->templObj = new mvb_coreTemplate();
        $templatePath = WPACCESS_TEMPLATE_DIR . 'admin_options.html';
        $this->template = $this->templObj->readTemplate($templatePath);
        $this->roles = get_option($wpdb->prefix . 'user_roles');
        $this->custom_caps = get_option(WPACCESS_PREFIX . 'custom_caps');
        if (!is_array($this->custom_caps)) {
            $this->custom_caps = array();
        }

        $roleList = array_keys($this->roles);
        /*
         * Expecting that there are more then 1 role :)
         * Any way is event one, not a big deal 
         */
        $defaultRole = ($roleList[0] == WPACCESS_ADMIN_ROLE ? $roleList[1] : $roleList[0]);
        if (!$curentRole) {
            $this->currentRole = $defaultRole;
        } else {
            /*
             * If someone tried to cheat
             */
            $this->currentRole = ($curentRole != WPACCESS_ADMIN_ROLE ? $curentRole : $defaultRole);
        }

        $this->currentParams = get_option(WPACCESS_PREFIX . 'options');
        $this->userSummary = count_users();
    }

    function getTemplate() {

        return $this->template;
    }

    function manage() {
        global $wpdb, $menu, $submenu;

        if (isset($_POST['submited'])) {
            $params = (isset($_POST['wpaccess']) ? $_POST['wpaccess'] : array());
            
            $this->currentParams[$this->currentRole] = array(
                'menu' => $this->prepareMenu($params),
                'metaboxes' => (isset($params[$this->currentRole]['metabox']) ? $params[$this->currentRole]['metabox'] : array()),
            );
            update_option(WPACCESS_PREFIX . 'options', $this->currentParams);

            /*
             * Update Role's Capabilities
             */
            $this->roles[$this->currentRole]['capabilities'] = (isset($params[$this->currentRole]['advance']) ? $params[$this->currentRole]['advance'] : array());
            update_option($wpdb->prefix . 'user_roles', $this->roles);
        }

        $mainHolder = $this->postbox('metabox-wpaccess-options', 'Options List', $this->getMainOptionsList());
        $this->template = $this->renderRoleSelector($this->template);
        $this->template = $this->renderDeleteRoleList($this->template);
        $content = $this->templObj->replaceSub('MAIN_OPTIONS_LIST', $mainHolder, $this->template);
        $markerArray = array(
            '###current_role###' => $this->roles[$this->currentRole]['name'],
            '###current_role_id###' => $this->currentRole,
            '###site_url###' => get_option('siteurl'),
            '###message_class###' => (isset($_POST['submited']) ? 'message-active' : 'message-passive'),
            '###nonce###' => wp_nonce_field(WPACCESS_PREFIX . 'options'),
        );
        $content = $this->templObj->updateMarkers($markerArray, $content);
        //add filter to future add-ons
        $content = apply_filters(WPACCESS_PREFIX . 'option_page', $content);

        if ($this->return) {
            return $content;
        } else {
            echo $content;
        }
    }
    
    function prepareMenu($params){
        
        $r_menu = (isset($params[$this->currentRole]['menu']) ? $params[$this->currentRole]['menu'] : array());
        
        return $r_menu;
    }

    function renderDeleteRoleList($template) {

        $listTemplate = $this->templObj->retrieveSub('DELETE_ROLE_LIST', $template);
        $itemTemplate = $this->templObj->retrieveSub('DELETE_ROLE_ITEM', $listTemplate);
        $list = '';
        if (is_array($this->roles)) {
            foreach ($this->roles as $role => $data) {
                if ($role == WPACCESS_ADMIN_ROLE) {
                    continue;
                }
                $list .= $this->renderDeleteRoleItem($role, $data, $itemTemplate);
            }
        }
        $listTemplate = $this->templObj->replaceSub('DELETE_ROLE_ITEM', $list, $listTemplate);

        return $this->templObj->replaceSub('DELETE_ROLE_LIST', $listTemplate, $template);
    }

    function renderDeleteRoleItem($role, $data, $template = '') {
        /*
         * This is used for ajax
         */
        if (!$template) {
            $listTemplate = $this->templObj->retrieveSub('DELETE_ROLE_LIST', $this->template);
            $template = $this->templObj->retrieveSub('DELETE_ROLE_ITEM', $listTemplate);
        }
        $count = isset($this->userSummary['avail_roles'][$role]) ? $this->userSummary['avail_roles'][$role] : 0;
        $deleteTemplate = $this->templObj->retrieveSub('DELETE_ROLE_BUTTON', $template);
        $markerArray = array(
            '###role_id###' => esc_js($role),
            '###role_name###' => stripcslashes($data['name']),
            '###count###' => $count,
        );
        if (!$count) {
            $template = $this->templObj->replaceSub('DELETE_ROLE_BUTTON', $deleteTemplate, $template);
        } else {
            $template = $this->templObj->replaceSub('DELETE_ROLE_BUTTON', '', $template);
        }

        return $this->templObj->updateMarkers($markerArray, $template);
    }

    function getMainOptionsList() {

        $mainHolder = $this->templObj->retrieveSub('MAIN_OPTIONS_LIST', $this->template);

        return $this->renderMainMenuOptions($mainHolder);
    }

    function renderRoleSelector($template) {
        $listTemplate = $this->templObj->retrieveSub('ROLE_LIST', $template);
        $list = '';
        if (is_array($this->roles)) {
            foreach ($this->roles as $role => $data) {
                if ($role == WPACCESS_ADMIN_ROLE) {
                    continue;
                }

                $markers = array(
                    '###value###' => $role,
                    '###title###' => stripcslashes($data['name']) . '&nbsp;', //nicer view :)
                    '###selected###' => ($this->currentRole == $role ? 'selected' : ''),
                );
                $list .= $this->templObj->updateMarkers($markers, $listTemplate);
            }
        }

        return $this->templObj->replaceSub('ROLE_LIST', $list, $template);
    }

    function renderMainMenuOptions($template) {
        global $submenu, $capabilitiesDesc;

        $s_menu = $this->getRoleMenu();
        /*
         * First Tab - Main Menu
         */
        $listTemplate = $this->templObj->retrieveSub('MAIN_MENU_LIST', $template);
        $itemTemplate = $this->templObj->retrieveSub('MAIN_MENU_ITEM', $listTemplate);
        $sublistTemplate = $this->templObj->retrieveSub('MAIN_MENU_SUBLIST', $itemTemplate);
        $subitemTemplate = $this->templObj->retrieveSub('MAIN_MENU_SUBITEM', $sublistTemplate);
        $list = '';

        if (is_array($s_menu)) {
            foreach ($s_menu as $menuItem) {
                if (!$menuItem[0]) { //seperator
                    continue;
                }
                //render submenu
                $subList = '';
                if (isset($submenu[$menuItem[2]]) && is_array($submenu[$menuItem[2]])) {
                    foreach ($submenu[$menuItem[2]] as $submenuItem) {
                        $checked = $this->checkChecked('submenu', array($menuItem[2], $submenuItem[2]));

                        $markers = array(
                            '###submenu_name###' => $this->removeHTML($submenuItem[0]),
                            '###value###' => $submenuItem[2],
                            '###checked###' => $checked
                        );
                        $subList .= $this->templObj->updateMarkers($markers, $subitemTemplate);
                    }
                    $subList = $this->templObj->replaceSub('MAIN_MENU_SUBITEM', $subList, $sublistTemplate);
                }
                $tTempl = $this->templObj->replaceSub('MAIN_MENU_SUBLIST', $subList, $itemTemplate);
                $markers = array(
                    '###name###' => $this->removeHTML($menuItem[0]),
                    '###id###' => $menuItem[5],
                    '###menu###' => $menuItem[2],
                    '###role###' => $this->currentRole,
                    '###whole_checked###' => $this->checkChecked('menu', array($menuItem[2]))
                );
                $list .= $this->templObj->updateMarkers($markers, $tTempl);
            }
        }
        $listTemplate = $this->templObj->replaceSub('MAIN_MENU_ITEM', $list, $listTemplate);
        $template = $this->templObj->replaceSub('MAIN_MENU_LIST', $listTemplate, $template);
        /*
         * Second Tab - Metaboxes
         */
        $listTemplate = $this->renderMetaboxList($template);
        $template = $this->templObj->replaceSub('METABOX_LIST', $listTemplate, $template);
        /*
         * Third Tab - Advance Settings
         */
        $m = new module_User();
        $capList = $m->getAllCaps();

        $listTemplate = $this->templObj->retrieveSub('CAPABILITY_LIST', $template);
        $itemTemplate = $this->templObj->retrieveSub('CAPABILITY_ITEM', $listTemplate);
        $list = '';
        if (is_array($capList)) {
            foreach ($capList as $cap => $dump) {
                $desc = (isset($capabilitiesDesc[$cap]) ? htmlspecialchars($capabilitiesDesc[$cap], ENT_QUOTES) : '');
                $markers = array(
                    '###role###' => $this->currentRole,
                    '###title###' => $cap,
                    '###description###' => $desc,
                    '###checked###' => $this->checkChecked('capability', array($cap)),
                    '###cap_name###' => $m->getCapabilityHumanTitle($cap)
                );
                $titem = $this->templObj->updateMarkers($markers, $itemTemplate);
                if (!in_array($cap, $this->custom_caps)) {
                    $titem = $this->templObj->replaceSub('CAPABILITY_DELETE', '', $titem);
                } else {
                    $titem = $this->templObj->replaceSub('CAPABILITY_DELETE', $this->templObj->retrieveSub('CAPABILITY_DELETE', $titem), $titem);
                }
                $list .= $titem;
            }
        }
        $listTemplate = $this->templObj->replaceSub('CAPABILITY_ITEM', $list, $listTemplate);
        $template = $this->templObj->replaceSub('CAPABILITY_LIST', $listTemplate, $template);

        //Posts & Pages
        $template = $this->templObj->replaceSub('POST_INFORMATION', '', $template);

        return $template;
    }

    protected function getRoleMenu() {
        global $menu;

        $menu_order = get_option(WPACCESS_PREFIX . 'menu_order');

        $r_menu = $menu;
        ksort($r_menu);

        if (isset($menu_order[$this->currentRole]) && is_array($menu_order[$this->currentRole])) {//reorganize menu according to role
            if (is_array($menu)) {
                $w_menu = array();
                foreach ($menu_order[$this->currentRole] as $mid) {
                    foreach ($menu as $data) {
                        if (isset($data[5]) && ($data[5] == $mid)) {
                            $w_menu[] = $data;
                        }
                    }
                }
                $cur_pos = 0;
                foreach ($r_menu as &$data) {
                    for ($i = 0; $i < count($w_menu); $i++) {
                        if (isset($data[5]) && ($w_menu[$i][5] == $data[5])) {
                            $data = $w_menu[$cur_pos++];
                            break;
                        }
                    }
                }
            }
        }

        return $r_menu;
    }

    function renderMetaboxList($template) {
        global $wp_post_types;

        $listTemplate = $this->templObj->retrieveSub('METABOX_LIST', $template);

        $itemTemplate = $this->templObj->retrieveSub('METABOX_LIST_ITEM', $listTemplate);
        $list = '';


        if (isset($this->currentParams['settings']['metaboxes']) && is_array($this->currentParams['settings']['metaboxes'])) {
            $plistTemplate = $this->templObj->retrieveSub('POST_METABOXES_LIST', $itemTemplate);
            $pitemTemplate = $this->templObj->retrieveSub('POST_METABOXES_ITEM', $plistTemplate);

            foreach ($this->currentParams['settings']['metaboxes'] as $post_type => $metaboxes) {

                if (!isset($wp_post_types[$post_type])) {
                    if ($post_type != 'dashboard') {
                        continue;
                    }
                }

                $mList = '';
                foreach ($metaboxes as $position => $metaboxes1) {
                    foreach ($metaboxes1 as $priority => $metaboxes2) {

                        if (is_array($metaboxes2) && count($metaboxes2)) {
                            foreach ($metaboxes2 as $id => $data) {

                                if (is_array($data)) {
                                    //strip html for metaboxes. The reason - dashboard metaboxes
                                    $data['title'] = $this->removeHTML($data['title']);
                                    $markerArray = array(
                                        '###title###' => $this->removeHTML($data['title']),
                                        '###short_id###' => (strlen($data['id']) > 25 ? substr($data['id'], 0, 22) . '...' : $data['id']),
                                        '###id###' => $data['id'],
                                        '###priority###' => $priority,
                                        '###internal_id###' => $post_type . '-' . $id,
                                        '###position###' => $position,
                                        '###checked###' => $this->checkChecked('metabox', array($post_type . '-' . $id)),
                                        '###role###' => $this->currentRole
                                    );
                                    $mList .= $this->templObj->updateMarkers($markerArray, $pitemTemplate);
                                }
                            }
                        }
                    }
                }
                $tList = $this->templObj->replaceSub('POST_METABOXES_ITEM', $mList, $plistTemplate);
                $tList = $this->templObj->replaceSub('POST_METABOXES_LIST', $mList, $itemTemplate);
                $label = ($post_type != 'dashboard' ? $wp_post_types[$post_type]->labels->name : 'Dashboard');
                $list .= $this->templObj->updateMarkers(array('###post_type_label###' => $label), $tList);
            }
            $listTemplate = $this->templObj->replaceSub('METABOX_LIST_EMPTY', '', $listTemplate);
            $listTemplate = $this->templObj->replaceSub('METABOX_LIST_ITEM', $list, $listTemplate);
        } else {
            $emptyMessage = $this->templObj->retrieveSub('METABOX_LIST_EMPTY', $listTemplate);
            $listTemplate = $this->templObj->replaceSub('METABOX_LIST_ITEM', '', $listTemplate);
            $listTemplate = $this->templObj->replaceSub('METABOX_LIST_EMPTY', $emptyMessage, $listTemplate);
        }

        return $listTemplate;
    }

    function removeHTML($text) {
        $text = preg_replace(
                array(
            "'<span[^>]*?>.*?</span[^>]*?>'si",
                ), '', $text);

        $text = preg_replace('/<a[^>]*href[[:space:]]*=[[:space:]]*["\']?[[:space:]]*javascript[^>]*/i', '', $text);

        // Return clean content
        return $text;
    }

    function checkChecked($type, $args) {

        $checked = '';

        switch ($type) {
            case 'submenu':
                $c_menu = &$this->currentParams[$this->currentRole]['menu'];
                if (isset($c_menu[$args[0]])) {
                    if (isset($c_menu[$args[0]]['sub'][$args[1]]) || 
                            (isset($c_menu[$args[0]]['whole']) && $c_menu[$args[0]]['whole'])) {
                        $checked = 'checked';
                    }
                }
                break;

            case 'menu':
                $c_menu = &$this->currentParams[$this->currentRole]['menu'];
                if (isset($c_menu[$args[0]]['whole']) && $c_menu[$args[0]]['whole']) {
                    $checked = 'checked';
                }
                break;

            case 'capability':
                $c_cap = &$this->roles[$this->currentRole]['capabilities'];
                if (isset($c_cap[$args[0]])) {
                    $checked = 'checked';
                }
                break;

            case 'metabox':
                $c_meta = &$this->currentParams[$this->currentRole]['metaboxes'];
                if (isset($c_meta[$args[0]])) {
                    $checked = 'checked';
                }
                break;

            default:
                break;
        }

        return $checked;
    }

}

?>