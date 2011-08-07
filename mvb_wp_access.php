<?php

/*
  Plugin Name: Advanced Access Manager
  Description: Manage user roles and capabilities
  Version: 0.9.7
  Author: Vasyl Martyniuk
  Author URI: http://www.whimba.com
 */

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

/* Version check */
global $wp_version;

$exit_msg = 'Advanced Access Manager requires WordPress 3.1 or newer. '
        . '<a href="http://codex.wordpress.org/Upgrading_WordPress">Update now!</a>';

if (version_compare($wp_version, '3.1', '<')) {
    exit($exit_msg);
}

if (phpversion() < '5') {
    exit('Advanced Access Manager requires PHP 5 or newer');
}

require_once('mvb_config.php');

class mvb_WPAccess extends mvb_corePlugin {

    function __construct() {
        global $post;

        if (is_admin()) {

            if ($_GET['page'] == 'wp_access') {
                parent::__construct(WPACCESS_BASE_URL, WP_PLUGIN_DIR);
                //css
                wp_enqueue_style('jquery-ui', WPACCESS_CSS_URL . 'ui/jquery.ui.all.css');
                wp_enqueue_style('wpaccess-style', WPACCESS_CSS_URL . 'wpaccess_style.css');
            }
            //dashboard filters
            /*
             * TODO - Implement dashboard widget filtering
             */
            add_action('wp_network_dashboard_setup', array($this, 'wp_network_dashboard_setup'), 999);
            add_action('wp_user_dashboard_setup', array($this, 'wp_user_dashboard_setup'), 999);
            add_action('wp_dashboard_setup', array($this, 'wp_dashboard_setup'), 999);

            /*
             * Configure Plugin Environmnet
             */
            add_action('admin_menu', array($this, 'admin_menu'), 999);
            add_action('wp_print_scripts', array($this, 'wp_print_scripts'), 1);
            add_action('admin_action_render_rolelist', array($this, 'render_rolelist'));

            //ajax
            add_action('wp_ajax_mvbam', array($this, 'ajax'));

            /*
             * Initialize Metabox filter hook
             * Temporary - This was the only way to inject into process of metabox
             * rendering
             */
            if (isset($_GET['post'])) {
                $post_type = get_post_field('post_type', (int) $_GET['post']);
            } elseif (isset($_POST['post_ID'])) {
                $post_type = get_post_field('post_type', (int) $_POST['post_ID']);
            } elseif (isset($_REQUEST['post_type'])) {
                $post_type = trim($_REQUEST['post_type']);
            } else {
                $post_type = 'post';
            }

            if ($post_type) {
                add_action("do_meta_boxes", array($this, 'metaboxes'), 999, 3);
            }
        }
        /*
         * Main Hook, used to check if user is authorized to do an action
         * Executes after WordPress environment loaded and configured
         */
        add_action('wp_loaded', array($this, 'check'), 999);
    }

    /*
     * Ajax interface
     */

    public function ajax() {

        switch ($_POST['sub_action']) {
            case 'restore_role':
                $this->restore_role($_POST['role']);
                break;

            case 'create_role':
                $this->create_role();
                break;

            case 'delete_role':
                $this->delete_role();
                break;

            case 'render_metabox_list':
                $this->render_metabox_list();
                break;

            case 'initiate_wm':
                $this->initiate_wm();
                break;

            case 'initiate_url':
                $this->initiate_url();
                break;

            default:
                die();
                break;
        }
    }

    /*
     * Restore default User Roles
     * 
     * @param string User Role
     * @return bool True if success
     */

    protected function restore_role($role) {
        global $wpdb;

        //get current roles settings
        $or_roles = get_option(WPACCESS_PREFIX . 'original_user_roles');
        $roles = get_option($wpdb->prefix . 'user_roles');
        $options = get_option(WPACCESS_PREFIX . 'options');

        if (isset($or_roles[$role]) && isset($roles[$role]) && ($role != 'administrator')) {
            $roles[$role] = $or_roles[$role];
            //save current setting to DB
            update_option($wpdb->prefix . 'user_roles', $roles);
            //unset all option with metaboxes and menu
            unset($options[$role]);
            update_option(WPACCESS_PREFIX . 'options', $options);

            $result = array('status' => 'success');
        } else {
            $result = array('status' => 'error');
        }

        die(json_encode($result));
    }

    /*
     * Initialize or filter the list of metaboxes
     * 
     * This function is responsible for initializing the list of metaboxes if
     * "grab" parameter with value "metabox" if precent on _GET global array.
     * In other way it filters the list of metaboxes according to user's Role
     * 
     * @param mixed Result of execution get_user_option() in user.php file
     * @param string $option User option name
     * @param int $user Optional. User ID
     * @return mixed
     */

    function metaboxes($post_type, $priority, $post) {
        global $wp_meta_boxes;

        $currentOptions = get_option(WPACCESS_PREFIX . 'options');

        /*
         * Check if this is a process of initialization the metaboxes.
         * This process starts when admin click on "Refresh List" or "Initialize list"
         * on User->Access Manager page
         */
        if (isset($_GET['grab']) && ($_GET['grab'] == 'metaboxes')) {
            if (!is_array($currentOptions['settings'])) {
                $currentOptions['settings'] = array();
            }
            if (!is_array($currentOptions['settings']['metaboxes'])) {
                $currentOptions['settings']['metaboxes'] = array();
            }

            $currentOptions['settings']['metaboxes'] = array_merge($currentOptions['settings']['metaboxes'], $wp_meta_boxes);
            update_option(WPACCESS_PREFIX . 'options', $currentOptions);
        } else {
            $screen = get_current_screen();
            $m = new module_filterMetabox();
            switch ($screen->id) {
                case 'dashboard':
                    $m->manage('dashboard');
                    break;

                default:
                    $m->manage();
                    break;
            }
        }
    }

    /*
     * Activation hook
     * 
     * Save default user settings
     */

    function activate() {
        global $wpdb;

        //get current roles settings
        $roles = get_option($wpdb->prefix . 'user_roles');
        //save current setting to DB
        update_option(WPACCESS_PREFIX . 'original_user_roles', $roles);
    }

    /*
     * Deactivation hook
     * 
     * Delete all record in DB related to current plugin
     * Restore original user roles
     */

    function deactivate() {

        $roles = get_option(WPACCESS_PREFIX . 'original_user_roles');

        if (is_array($roles) && count($roles)) {
            update_option($wpdb->prefix . 'user_roles', $roles);
        }
        delete_option(WPACCESS_PREFIX . 'original_user_roles');
        delete_option(WPACCESS_PREFIX . 'options');
    }

    /*
     * Print general JS files and localization
     * 
     */

    function wp_print_scripts() {
        if ($_GET['page'] == 'wp_access') {
            parent::scripts();
            wp_enqueue_script('jquery-ui', WPACCESS_JS_URL . 'ui/jquery-ui.min.js');
            wp_enqueue_script('wpaccess-admin', WPACCESS_JS_URL . 'admin-options.js');
            wp_enqueue_script('jquery-tooltip', WPACCESS_JS_URL . 'jquery.tools.min.js');
            wp_localize_script('wpaccess-admin', 'wpaccessLocal', array(
                'handlerURL' => site_url() . '/wp-admin/index.php', //can't use admin-ajax.php in fact it doesn't load menu and submenu
                'nonce' => wp_create_nonce(WPACCESS_PREFIX . 'ajax')
            ));
        }
    }

    /*
     * Initialize Widgets and Metaboxes
     * 
     * Part of AJAX interface. Using for metabox and widget initialization.
     * Go through the list of all registered post types and with http request
     * try to access the edit page and grab the list of rendered metaboxes.
     * 
     * @return string JSON encoded string with result
     */

    function initiate_wm() {
        global $wp_post_types;

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        /*
         * Go through the list of registered post types and try to grab
         * rendered metaboxes
         * Parameter next in _POST array shows the next port type in list of
         * registered metaboxes. This is done for emulating the progress bar
         * after clicking "Refresh List" or "Initialize List"
         */
        $next = trim($_POST['next']);
        $typeList = array_keys($wp_post_types);
        //add dashboard
        array_unshift($typeList, 'dashboard');
        $typeQuant = count($typeList);

        if ($next) { //if next present, means that process continuing
            $i = 0;
            while ($typeList[$i] != $next) { //find post type
                $i++;
            }
            $current = $next;
            if ($typeList[$i + 1]) { //continue the initialization process?
                $next = $typeList[$i + 1];
            } else {
                $next = FALSE;
            }
        } else { //this is the beggining
            $current = $typeList[0];
            $next = isset($typeList[1]) ? $typeList[1] : '';
        }
        if ($current == 'dashboard') {
            $url = admin_url('index.php') . '?grab=metaboxes';
        } else {
            $url = admin_url('post-new.php?post_type=' . $current) . '&grab=metaboxes';
        }
  
        //grab metaboxes
        $result = $this->cURL($url);

        $result['value'] = round((($i + 1) / $typeQuant) * 100); //value for progress bar
        $result['next'] = ($next ? $next : '' ); //if empty, stop initialization

        die(json_encode($result));
    }

    /*
     * Initialize single URL
     * 
     * Sometimes not all metaboxes are rendered if there are conditions. For example
     * render Shipping Address Metabox if status of custom post type is Approved.
     * So this metabox will be not visible during general initalization in function
     * initiateWM(). That is why this function do that manually
     * 
     * @return string JSON encoded string with result
     */

    function initiate_url() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $url = esc_url($_POST['url']);
        if ($url) {
            $url = add_query_arg('grab', 'metaboxes', $url);
            $result = $this->cURL($url);
        } else {
            $result = array('status' => 'error');
        }

        die(json_encode($result));
    }

    /*
     * Initiate HTTP request
     * 
     * @param string Requested URL
     * @return bool Always return TRUE
     */

    function cURL($url) {
        $header = array(
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']
        );

        if (is_array($_COOKIE)) {
            foreach ($_COOKIE as $key => $value) {
                $cookies[] = new WP_Http_Cookie(array(
                            'name' => $key,
                            'value' => $value
                        ));
            }
        }

        $res = wp_remote_request($url, array(
            'headers' => $header,
            'cookies' => $cookies,
            'timeout' => 5
                ));

        if ($res instanceof WP_Error) {
            $result = array(
                'status' => 'error',
                'url' => $url
            );
        } else {
            $result = array('status' => 'success');
        }

        return $result;
    }

    /*
     * Render metabox list after initialization
     * 
     * Part of AJAX interface. Is used for rendering the list of initialized
     * metaboxes.
     * 
     * @return string HTML string with result
     */

    function render_metabox_list() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $m = new module_optionManager($_POST['role']);
        die($m->renderMetaboxList($m->getTemplate()));
    }

    function create_role() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $m = new module_Roles();
        $result = $m->createNewRole($_POST['role']);
        if ($result['result'] == 'success') {
            $m = new module_optionManager($result['new_role']);
            $result['html'] = $m->renderDeleteRoleItem($result['new_role'], array('name' => $_POST['role']));
        }

        die(json_encode($result));
    }

    function delete_role() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $m = new module_Roles();
        $m->remove_role($_POST['role']);
        die();
    }

    function render_rolelist() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $m = new module_optionManager($_POST['role']);
        die($m->getMainOptionsList());
    }

    function wp_dashboard_setup() {
        global $wp_meta_boxes, $wp_post_types;

        //TODO
    }

    /*
     * Main function for checking if user has access to a page
     * 
     * Check if current user has access to requested page. If no, print an
     * notification
     * 
     */

    function check() {
        $uri = $_SERVER['REQUEST_URI'];

        $m = new module_filterMenu();

        if (!$m->checkAccess($uri)) {
            wp_die('<p>' . __('You do not have sufficient permissions to perform this action') . '</p>');
        }
    }

    /*
     * Main function for menu filtering
     * 
     * Add Access Manager submenu to User main menu and additionality filter
     * the Main Menu according to settings
     * 
     */

    function admin_menu() {
        add_submenu_page('users.php', __('Access Manager'), __('Access Manager'), 'administrator', 'wp_access', array($this, 'manager_page'));

        //filter the menu
        $m = new module_filterMenu();
        $m->manage();
    }

    /*
     * Option page renderer
     */

    function manager_page() {
        $m = new module_optionManager($_POST['current_role']);
        $m->manage();
    }

}

register_activation_hook(__FILE__, array('mvb_WPAccess', 'activate'));
register_deactivation_hook(__FILE__, array('mvb_WPAccess', 'deactivate'));
add_action('init', 'init_wpaccess');
?>