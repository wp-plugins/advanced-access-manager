<?php

/*
  Plugin Name: Advanced Access Manager
  Description: Manage Access for all User Roles to WordPress Backend and Frontend.
  Version: 1.3
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


/*
 * =============================================================================
 *                        ALL DEVELOPERS NOTIFICATION
 *                        ===========================
 * If you read this message it means you are interested in code and can be treated
 * as a developer.
 * I'm not recommending for current version of plugin, do patches or add-ons.
 * Version 2.0 will be totally different and will follow MVC patterns.
 * There are some filters already implemented in this plugin so I'll leave them
 * in latest version for compatibility reasons.
 * If you have any questions of want to participate in this project, contact me
 * via e-mail whimba@gmail.com 
 * =============================================================================
 */

/* Version check */
global $wp_version;

$exit_msg = 'Advanced Access Manager requires WordPress 3.1 or newer. '
        . '<a href="http://codex.wordpress.org/Upgrading_WordPress">Update now!</a>';

//error_reporting(E_ALL);

if (version_compare($wp_version, '3.1', '<')) {
    exit($exit_msg);
}

if (phpversion() < '5.0') {
    exit('Advanced Access Manager requires PHP 5.0 or newer');
}

require_once('mvb_config.php');

class mvb_WPAccess extends mvb_corePlugin {
    /*
     * Holds the restriction options
     * 
     * @var array
     * @access protected
     */

    protected $restrictions;

    /*
     * Module User
     * 
     * @var object
     * @access public
     */
    public $user;

    /*
     * Is Multisite or not
     * 
     * @var bool
     * @access public
     */
    public static $allow_ms = FALSE;

    /*
     * Is Super Admin
     * 
     * If Multisite allowed and is super admin then include
     * administrator role to manage
     * 
     * @var bool
     * @access public
     */
    public $is_super = FALSE;

    /*
     * Current Blog Info
     * 
     * @var array
     * @access public
     */
    public static $current_blog;

    /*
     * Module Roles
     * 
     * @var object
     * @access protected
     */
    protected $roles;

    /*
     * Skip filter categories during initialization
     * 
     * @var bool
     * @access protected
     */
    protected $skip_filtering = TRUE;

    public function __construct() {
        global $post;


        /*
         * Configure Plugin Environmnet
         */
        self::$allow_ms = $this->init_multisite();

        //TODO - Optimize this
        $this->user = new module_User($this);
        $this->roles = new module_Roles();
        $this->menu = new module_filterMenu($this);
        $this->is_super = is_super_admin();

        if (is_admin()) {

            if (isset($_GET['page']) && ($_GET['page'] == 'wp_access')) {
                parent::__construct();
                add_action('admin_print_scripts', array($this, 'admin_print_scripts'));
                add_action('admin_print_styles', array($this, 'admin_print_styles'));
            }

            add_action('admin_menu', array($this, 'admin_menu'), 999);
            add_action('admin_action_render_rolelist', array($this, 'render_rolelist'));
            //Add Capabilities WP core forgot to
            add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);

            //help filter
            add_filter('contextual_help', array($this, 'contextual_help'), 10, 3);

            //ajax
            add_action('wp_ajax_mvbam', array($this, 'ajax'));
            add_action("do_meta_boxes", array($this, 'metaboxes'), 999, 3);
        } else {
            add_action('wp_before_admin_bar_render', array($this, 'wp_before_admin_bar_render'));
            add_action('wp', array($this, 'wp_front'));
            add_filter('get_pages', array($this, 'get_pages'));
        }

        add_filter('get_terms', array($this, 'get_terms'), 10, 3);
        add_action('pre_get_posts', array($this, 'pre_get_posts'));
        /*
         * Main Hook, used to check if user is authorized to do an action
         * Executes after WordPress environment loaded and configured
         */
        add_action('wp_loaded', array($this, 'check'), 999);
    }

    /*
     * ===============================================================
     *   ******************* PUBLIC METHODS ************************
     * ===============================================================
     */

    public function get_current_version() {

        $plugins = get_plugins();

        if (isset($plugins[WPACCESS_DIRNAME . '/mvb_wp_access.php'])) {
            $version = $plugins[WPACCESS_DIRNAME . '/mvb_wp_access.php']['Version'];
        } else {
            $version = '1.0';
        }

        return $version;
    }

    /*
     * Hook to init session for swfupload
     * 
     */

    public function admin_print_styles() {
        wp_enqueue_style('jquery-ui', WPACCESS_CSS_URL . 'ui/jquery-ui-1.8.16.custom.css');
        wp_enqueue_style('wpaccess-style', WPACCESS_CSS_URL . 'wpaccess_style.css');
        wp_enqueue_style('wpaccess-treeview', WPACCESS_CSS_URL . 'treeview/jquery.treeview.css');
    }

    public function wp_front($wp) {
        global $post, $page, $wp_query;

        if (!$wp_query->is_home() && $post) {
            if ($this->checkPostAccess($post)) {
                wp_redirect(home_url());
            }
        }
    }

    /*
     * Render Help context for Option page
     * 
     * @param
     * @param
     * @param
     * @return string
     */

    public function contextual_help($contextual_help, $screenID, $screen) {
        global $help_context;

        if ($screenID == 'users_page_wp_access') {
            $contextual_help = $help_context;
        }

        return $contextual_help;
    }

    /*
     * Filter Admin Top Bar
     * 
     */

    public function wp_before_admin_bar_render() {
        global $wp_admin_bar;

        if (is_object($wp_admin_bar) && isset($wp_admin_bar->menu)) {
            $this->filter_top_bar($wp_admin_bar->menu);
        }
    }

    /*
     * Filter Front Menu
     * 
     */

    public function get_pages($pages) {

        if (is_array($pages)) { //filter all pages which are not allowed
            foreach ($pages as $i => $page) {
                if ($this->checkPostAccess($page)) {
                    unset($pages[$i]);
                }
            }
        }

        return $pages;
    }

    public function pre_get_posts($query) {

        $user_roles = $this->user->getCurrentUserRole();
        if (!count($user_roles)) { //apply restriction of all registered roles
            $user_roles = $this->getAllRoles();
        }
        $r_posts = array();
        $r_cats = array();
        foreach ($user_roles as $role) {
            if (!isset($this->restrictions[$role])) {
                continue;
            }
            if (isset($this->restrictions[$role]['categories']) && is_array($this->restrictions[$role]['categories'])) {
                //get list of all categories
                $t_posts = array();
               
                foreach ($this->restrictions[$role]['categories'] as $id => $data) {
                    $exclude = FALSE;
                    if (is_admin() && $data['restrict']) {
                        $exclude = TRUE;
                    } elseif (!is_admin() && $data['restrict_front']) {
                        $exclude = TRUE;
                    }
                    if ($exclude) {
                        if (isset($r_cats[$data['taxonomy']])) {
                            $r_cats[$data['taxonomy']]['terms'][] = $id;
                        } else {
                            $r_cats[$data['taxonomy']] = array(
                                'taxonomy' => $data['taxonomy'],
                                'terms' => array($id),
                                'field' => 'term_id',
                                'operator' => 'NOT IN',
                            );
                        }
                    }
                }
            }
            if (isset($this->restrictions[$role]['posts']) && is_array($this->restrictions[$role]['posts'])) {
                //get list of all posts
                foreach ($this->restrictions[$role]['posts'] as $id => $data) {
                    if (is_admin() && $data['restrict']) {
                        $t_posts[] = $id;
                    } elseif (!is_admin() && $data['restrict_front']) {
                        $t_posts[] = $id;
                    }
                }
                $r_posts = array_merge($r_posts, $t_posts);
            }
        }

        $query->query_vars['tax_query'] = $r_cats;
        $query->query_vars['post__not_in'] = $r_posts;
        //   debug($query->query_vars);
    }

    public function get_terms($terms, $taxonomies, $args) {

        if (is_array($terms) && !$this->skip_filtering) {
            $user_roles = $this->user->getCurrentUserRole();
            if (!count($user_roles)) {
                $user_roles = $this->getAllRoles();
            }
            foreach ($terms as $i => $term) {
                foreach ($user_roles as $role) {
                    if ($this->checkCategoryAccess($role, array($term->term_id))) {
                        unset($terms[$i]);
                    }
                }
            }
        }

        return $terms;
    }

    public function map_meta_cap($caps, $cap, $user_id, $args) {

        switch ($cap) {
            case 'edit_comment':
                $caps[] = 'edit_comment';
                break;

            default:
                break;
        }

        return $caps;
    }

    /*
     * Ajax interface
     */

    public function ajax() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        switch ($_REQUEST['sub_action']) {
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

            case 'import_config':
                $this->import_config();
                break;

            case 'add_capability':
                $this->add_capability();
                break;

            case 'delete_capability':
                $this->delete_capability();
                break;

            case 'get_treeview':
                $this->get_treeview();
                break;

            case 'get_info':
                $this->get_info();
                break;

            case 'save_info':
                $this->save_info();
                break;

            case 'check_addons':
                $this->check_addons();
                break;

            case 'save_order':
                $this->save_order();
                break;

            case 'export':
                $this->export();
                break;

            case 'upload_config':
                $this->upload_config();
                break;

            default:
                die();
                break;
        }
    }

    public function edit_term_link($term) {

        $st = $this->short_title($term->name);
        $link = '<a href="' . get_edit_term_link($term->term_id, 'category') . '" target="_blank" title="' . esc_attr($term->name) . '">' . $st . '</a>';

        return $link;
    }

    public static function get_blog_option($opt, $default = '') {

        $blog = self::$current_blog;
        if (self::$allow_ms) {
            $option = get_blog_option($blog['id'], $blog['prefix'] . $opt, $default);
        } else {
            $option = get_option($blog['prefix'] . $opt, $default);
        }

        return $option;
    }

    public static function update_blog_option($opt, $data) {

        $blog = self::$current_blog;
        if (self::$allow_ms) {
            update_blog_option($blog['id'], $blog['prefix'] . $opt, $data);
        } else {
            update_option($blog['prefix'] . $opt, $data);
        }
    }

    public static function delete_blog_option($opt) {

        $blog = self::$current_blog;
        if (self::$allow_ms) {
            $option = delete_blog_option($blog['id'], $blog['prefix'] . $opt);
        } else {
            $option = delete_option($blog['prefix'] . $opt);
        }
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

    public function metaboxes($post_type, $priority, $post) {
        global $wp_meta_boxes;

        $c_options = $this->get_blog_option(WPACCESS_PREFIX . 'options');

        /*
         * Check if this is a process of initialization the metaboxes.
         * This process starts when admin click on "Refresh List" or "Initialize list"
         * on User->Access Manager page
         */
        if (isset($_GET['grab']) && ($_GET['grab'] == 'metaboxes')) {
            if (!is_array($c_options['settings']['metaboxes'][$post_type])) {
                $c_options['settings']['metaboxes'][$post_type] = array();
            }

            if (is_array($wp_meta_boxes[$post_type])) {
                /*
                 * Optimize the saving data
                 * Go throught the list of metaboxes and delete callback and args
                 */
                foreach ($wp_meta_boxes[$post_type] as $pos => $levels) {
                    if (is_array($levels)) {
                        foreach ($levels as $level => $boxes) {
                            if (is_array($boxes)) {
                                foreach ($boxes as $box => $data) {
                                    $c_options['settings']['metaboxes'][$post_type][$pos][$level][$box] = array(
                                        'id' => $data['id'],
                                        'title' => $data['title']
                                    );
                                }
                            }
                        }
                    }
                }
                $this->update_blog_option(WPACCESS_PREFIX . 'options', $c_options);
            }
        } else {
            $screen = get_current_screen();
            $m = new module_filterMetabox($this);
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

    public function activate() {
        global $wpdb;

        //BECAUSE STATIC CALLING
        self::$allow_ms = (is_multisite() && is_network_admin());
        self::$current_blog = self::get_current_blog();

        $role_list = self::get_blog_option('user_roles');
        //save current setting to DB
        self::update_blog_option(WPACCESS_PREFIX . 'original_user_roles', $role_list);
        //add options
        $m = new module_Roles();
        $roles = (is_array($m->roles) ? array_keys($m->roles) : array());
        $options = array();
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (($role == WPACCESS_ADMIN_ROLE) && !is_super_admin()) {
                    continue;
                }
                $options[$role] = array(
                    'menu' => array(),
                    'metaboxes' => array(),
                    'capabilities' => $m->roles[$role]['capabilities']
                );
            }
        }
        self::update_blog_option(WPACCESS_PREFIX . 'options', $options);

        //add custom capabilities
        $custom_caps = self::get_blog_option(WPACCESS_PREFIX . 'custom_caps');
        if (!is_array($custom_caps)) {
            $custom_caps = array();
        }
        $custom_caps[] = 'edit_comment';
        self::update_blog_option(WPACCESS_PREFIX . 'custom_caps', $custom_caps);
        $role_list[WPACCESS_ADMIN_ROLE]['capabilities']['edit_comment'] = 1; //add this role for admin automatically
        self::update_blog_option('user_roles', $role_list);
    }

    /*
     * Deactivation hook
     * 
     * Delete all record in DB related to current plugin
     * Restore original user roles
     */

    public function deactivate() {

        //BECAUSE STATIC CALLING
        self::$allow_ms = (is_multisite() && is_network_admin());
        self::$current_blog = self::get_current_blog();

        $roles = self::get_blog_option(WPACCESS_PREFIX . 'original_user_roles');

        if (is_array($roles) && count($roles)) {
            self::update_blog_option('user_roles', $roles);
        }
        self::delete_blog_option(WPACCESS_PREFIX . 'original_user_roles');
        self::delete_blog_option(WPACCESS_PREFIX . 'options');
        self::delete_blog_option(WPACCESS_PREFIX . 'restrictions');
        self::delete_blog_option(WPACCESS_PREFIX . 'menu_order');
        self::delete_blog_option(WPACCESS_PREFIX . 'key_params');
    }

    /*
     * Print general JS files and localization
     * 
     */

    public function admin_print_scripts() {

        parent::scripts();
        wp_enqueue_script('jquery-ui', WPACCESS_JS_URL . 'ui/jquery-ui.min.js');
        wp_enqueue_script('jquery-treeview', WPACCESS_JS_URL . 'treeview/jquery.treeview.js');
        wp_enqueue_script('jquery-treeedit', WPACCESS_JS_URL . 'treeview/jquery.treeview.edit.js');
        wp_enqueue_script('jquery-treeview-ajax', WPACCESS_JS_URL . 'treeview/jquery.treeview.async.js');
        wp_enqueue_script('jquery-fileupload', WPACCESS_JS_URL . 'fileupload/jquery.fileupload.js');
        wp_enqueue_script('jquery-fileupload-iframe', WPACCESS_JS_URL . 'fileupload/jquery.iframe-transport.js');
        wp_enqueue_script('wpaccess-admin', WPACCESS_JS_URL . 'admin-options.js');
        wp_enqueue_script('jquery-tooltip', WPACCESS_JS_URL . 'jquery.tools.min.js');
        $locals = array(
            'nonce' => wp_create_nonce(WPACCESS_PREFIX . 'ajax'),
            'css' => WPACCESS_CSS_URL,
            'js' => WPACCESS_JS_URL,
        );
        if (self::$allow_ms) {
            //can't use admin-ajax.php in fact it doesn't load menu and submenu
            $locals['handlerURL'] = get_admin_url(self::$current_blog['id'], 'index.php');
            $locals['ajaxurl'] = get_admin_url(self::$current_blog['id'], 'admin-ajax.php');
        } else {
            $locals['handlerURL'] = admin_url('index.php');
            $locals['ajaxurl'] = admin_url('admin-ajax.php');
        }

        wp_localize_script('wpaccess-admin', 'wpaccessLocal', $locals);
    }

    /*
     * Main function for checking if user has access to a page
     * 
     * Check if current user has access to requested page. If no, print an
     * notification
     * 
     */

    public function check() {
        global $wp_query, $restrict_message;

        $this->restrictions = $this->getRestrictions();

        if (is_admin()) {
            $uri = $_SERVER['REQUEST_URI'];

            //TODO - Move this action to checkAcess
            $access = $this->menu->checkAccess($uri);
            //filter
            $access = apply_filters(WPACCESS_PREFIX . 'check_access', $access, $uri);

            if (!$access) {
                wp_die($restrict_message);
            }

            //check if user try to access a post
            if (isset($_GET['post'])) {
                $post_id = (int) $_GET['post'];
            } elseif (isset($_POST['post_ID'])) {
                $post_id = (int) $_POST['post_ID'];
            } else {
                $post_id = 0;
            }

            //aam_debug($this->restrictions);
            if ($post_id) { //check if current user has access to current post
                $post = get_post($post_id);
                if ($this->checkPostAccess($post)) {
                    wp_die($restrict_message);
                }
            } elseif (isset($_GET['taxonomy']) && isset($_GET['tag_ID'])) { // TODO - Find better way
                $user_roles = $this->user->getCurrentUserRole();
                if (!count($user_roles)) {
                    $user_roles = $this->getAllRoles();
                }
                foreach ($user_roles as $role) {
                    if ($this->checkCategoryAccess($role, array($_GET['tag_ID']))) {
                        wp_die($restrict_message);
                    }
                }
            }
        } else {
            if (is_category()) {
                $cat_obj = $wp_query->get_queried_object();
                $user_roles = $this->user->getCurrentUserRole();
                if (!count($user_roles)) {
                    $user_roles = $this->getAllRoles();
                }
                foreach ($user_roles as $role) {
                    if (!$this->checkCategoryAccess($role, array($cat_obj->term_id))) {
                        wp_redirect(home_url());
                    }
                }
            } else {
                //leave rest for "wp" action
            }
        }
    }

    /*
     * Main function for menu filtering
     * 
     * Add Access Manager submenu to User main menu and additionality filter
     * the Main Menu according to settings
     * 
     */

    public function admin_menu() {

        $cap = ($this->is_super ? 'administrator' : 'aam_manage');

        add_submenu_page('users.php', __('Access Manager'), __('Access Manager'), $cap, 'wp_access', array($this, 'manager_page'));

        //init the list of key parameters
        $this->init_key_params();
        //filter the menu
        $this->menu->manage();
    }

    public function manager_page() {

        $c_role = isset($_REQUEST['current_role']) ? $_REQUEST['current_role'] : FALSE;
        if (self::$allow_ms) {
            if (is_array(self::$current_blog)) { //TODO -IMPLEMENT ERROR IF SITE NOT FOUND
                $m = new module_optionManager($this, $c_role);
                $m->do_save();
                $url = add_query_arg(array('page' => 'wp_access', 'current_role' => $c_role), get_admin_url(self::$current_blog['id'], 'users.php'));
                $result = $this->cURL($url, TRUE, TRUE);
                $content = phpQuery::newDocument($result['content']);
                echo apply_filters(WPACCESS_PREFIX . 'option_page', $content['#aam_wrap']->htmlOuter());
                unset($content);
            }
        } else {
            $m = new module_optionManager($this, $c_role);
            $m->do_save();
            $m->manage();
        }
    }

    public function render_rolelist() {

        $m = new module_optionManager($this, $_POST['role']);
        $or_roles = $this->get_blog_option(WPACCESS_PREFIX . 'original_user_roles');
        $result = array(
            'html' => $m->getMainOptionsList(),
            'restorable' => (isset($or_roles[$_POST['role']]) ? TRUE : FALSE)
        );

        die(json_encode($result));
    }

    /*
     * ===============================================================
     *  ******************** PROTECTED METHODS **********************
     * ===============================================================
     */

    /*
     * Initialize Multisite variable
     * 
     * Check if Multisite supported and init necessary variables
     * 
     * @return bool TRUE if Multisite supported and is Network Dashboard
     */

    protected function init_multisite() {

        $allow_ms = (is_multisite() && is_network_admin() ? TRUE : FALSE);
        if ($allow_ms) {
            add_action('network_admin_menu', array($this, 'admin_menu'), 999);
        }
        self::$current_blog = $this->get_current_blog();

        return $allow_ms;
    }

    /*
     * Get current post type
     * 
     * @return string Current Post Type
     */

    protected function get_cPost() {

        if (isset($_GET['post'])) {
            $post_type = get_post_field('post_type', (int) $_GET['post']);
        } elseif (isset($_POST['post_ID'])) {
            $post_type = get_post_field('post_type', (int) $_POST['post_ID']);
        } elseif (isset($_REQUEST['post_type'])) {
            $post_type = trim($_REQUEST['post_type']);
        } else {
            $post_type = 'post';
        }

        return $post_type;
    }

    /*
     * Uploading file
     * 
     */

    protected function upload_config() {

        $result = 0;
        if (isset($_FILES["config_file"])) {
            $fdata = $_FILES["config_file"];
            if (is_uploaded_file($fdata["tmp_name"]) && ($fdata["error"] == 0)) {
                $file_name = 'import_' . uniqid() . '.ini';
                $file_path = WPACCESS_BASE_DIR . 'backups/' . $file_name;
                $result = move_uploaded_file($fdata["tmp_name"], $file_path);
            }
        }

        $data = array(
            'status' => ($result ? 'success' : 'error'),
            'file_name' => $file_name
        );

        die(json_encode($data));
    }

    /*
     * Import configurations
     * 
     */

    protected function import_config() {

        $m = new module_optionManager($this);
        $result = $m->import_config();

        die(json_encode($result));
    }

    /*
     * Export configurations
     * 
     */

    protected function export() {

        $file = $this->render_config();
        $file_b = basename($file);

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file_b));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
        }

        die();
    }

    /*
     * Render Config File
     * 
     */

    private function render_config() {

        $file_path = WPACCESS_BASE_DIR . 'backups/' . uniqid(WPACCESS_PREFIX) . '.ini';
        $m = new module_optionManager($this);
        $m->render_config($file_path);

        return $file_path;
    }

    /*
     * Check Category Restriction
     * 
     * @param string Role
     * @param array List of Categories
     * @return bool TRUE if restricted
     */

    protected function checkCategoryAccess($role, $t_list) {

        $restrict = FALSE;

        if (isset($this->restrictions[$role])) {
            $r_info = $this->restrictions[$role];
        }
        if (isset($r_info['categories']) && is_array($r_info['categories'])) {//check if no restriction on categoris
            $c_list = array();
            foreach ($r_info['categories'] as $id => $data) {
                if (is_admin() && $data['restrict']) {
                    $c_list[] = $id;
                } elseif (!is_admin() && $data['restrict_front']) {
                    $c_list[] = $id;
                }
            }
 
            if (count(array_intersect($t_list, $c_list))) {
                $restrict = TRUE;
            }
        }

        return $restrict;
    }

    /*
     * Check if user has access to current post
     * 
     * @param int Post ID
     */

    protected function checkPostAccess($post) {
        global $restrict_message;

        $restrict = FALSE;
        $user_roles = $this->user->getCurrentUserRole();
        if (!count($user_roles)) {
            $user_roles = $this->getAllRoles();
        }
        //get post's categories
        $taxonomies = get_object_taxonomies($post);

        $cat_list = wp_get_object_terms($post->ID, $taxonomies, array('fields' => 'ids'));

        while (list($i, $role) = each($user_roles)) {
            if ($this->checkCategoryAccess($role, $cat_list)) {
                $restrict = TRUE;
                break;
            }
            $r_info = (isset($this->restrictions[$role]['posts']) ? $this->restrictions[$role]['posts'] : FALSE);

            if (is_array($r_info)) {
                //check if post exists in restriction list
                if (isset($r_info[$post->ID])) {
                    if (is_admin() && $r_info[$post->ID]['restrict']) {
                        $restrict = TRUE;
                    } elseif (!is_admin() && $r_info[$post->ID]['restrict_front']) {
                        $restrict = TRUE;
                    }
                }
                if ($restrict) {
                    break;
                }
            }
        }

        return $restrict;
    }

    protected function getRestrictions() {

        $rests = array();

        if (!$this->is_super) { //is super admin - no restrictions at all
            $rests = $this->get_blog_option(WPACCESS_PREFIX . 'restrictions');
            if (!is_array($rests)) {
                $rests = array();
            }

            /*
             * Prepare list of all categories and subcategories
             * Why is this dynamically?
             * This part initiates each time because you can reogranize the
             * category tree after appling restiction to category
             */
            $this->skip_filtering = TRUE; //this is for get_term_children
            foreach ($rests as $role => $data) {
                if (isset($data['categories']) && is_array($data['categories'])) {
                    foreach ($data['categories'] as $cat_id => $restrict) {
                        //now check combination of options
                        $r = $this->checkExpiration($restrict);
                        if ($r) {
                            $data['categories'][$cat_id]['restrict'] = ($r & WPACCESS_RESTRICT_BACK ? 1 : 0);
                            $data['categories'][$cat_id]['restrict_front'] = ($r & WPACCESS_RESTRICT_FRONT ? 1 : 0);
                            //get list of all subcategories
                            $taxonomy = $this->get_taxonomy_by_term($cat_id);
                            $rests[$role]['categories'][$cat_id]['taxonomy'] = $taxonomy;
                            $cat_list = get_term_children($cat_id, $taxonomy);
                            if (is_array($cat_list)) {
                                foreach ($cat_list as $cid) {
                                    $rests[$role]['categories'][$cid] = $data['categories'][$cat_id];
                                }
                            }
                        } else {
                            unset($rests[$role]['categories'][$cat_id]);
                        }
                    }
                }
                //prepare list of posts and pages
                if (isset($data['posts']) && is_array($data['posts'])) {
                    foreach ($data['posts'] as $post_id => $restrict) {
                        //now check combination of options
                        $r = $this->checkExpiration($restrict);
                        if ($r) {
                            $rests[$role]['posts'][$post_id]['restrict'] = ($r & WPACCESS_RESTRICT_BACK ? 1 : 0);
                            $rests[$role]['posts'][$post_id]['restrict_front'] = ($r & WPACCESS_RESTRICT_FRONT ? 1 : 0);
                        } else {
                            unset($rests[$role]['posts'][$post_id]);
                        }
                    }
                }
            }
            $this->skip_filtering = FALSE;
        }

        return $rests;
    }

    protected function checkExpiration($data) {

        $result = WPACCESS_RESTRICT_NO;
        if (($data['restrict'] || $data['restrict_front']) && !trim($data['expire'])) {
            $result = ($data['restrict'] ? $result | WPACCESS_RESTRICT_BACK : $result);
            $result = ($data['restrict_front'] ? $result | WPACCESS_RESTRICT_FRONT : $result);
        } elseif (($data['restrict'] || $data['restrict_front']) && trim($data['expire'])) {
            if ($data['expire'] >= time()) {
                $result = ($data['restrict'] ? $result | WPACCESS_RESTRICT_BACK : $result);
                $result = ($data['restrict_front'] ? $result | WPACCESS_RESTRICT_FRONT : $result);
            }
        } elseif (trim($data['expire'])) {
            if (time() <= $data['expire']) {
                $result = WPACCESS_RESTRICT_BOTH; //TODO - Think about it
            }
        }

        return $result;
    }

    protected function getAllRoles() {

        $roles = (is_array($this->roles->roles) ? array_keys($this->roles->roles) : array());

        return $roles;
    }

    /*
     * Save menu order
     * 
     */

    protected function save_order() {

        $apply_all = $_POST['apply_all'];
        $menu_order = $this->get_blog_option(WPACCESS_PREFIX . 'menu_order');
        if ($apply_all) {
            $roles = $this->getAllRoles();
            foreach ($roles as $role) {
                if (($role == WPACCESS_ADMIN_ROLE) && !$this->is_super) {
                    continue;
                }
                $menu_order[$role] = $_POST['menu'];
            }
        } else {
            if ($_POST['role'] == WPACCESS_ADMIN_ROLE && $this->is_super) {
                $menu_order[$_POST['role']] = $_POST['menu'];
            } elseif ($_POST['role'] != WPACCESS_ADMIN_ROLE) {
                $menu_order[$_POST['role']] = $_POST['menu'];
            }
        }

        $this->update_blog_option(WPACCESS_PREFIX . 'menu_order', $menu_order);

        die(json_encode(array('status' => 'success')));
    }

    /*
     * Check if new addons available
     * 
     */

    protected function check_addons() {

        //grab list of features
        $url = 'http://whimba.com/features.php';
        //second paramter is FALSE, which means that I'm not sending any
        //cookies to my website
        $response = $this->cURL($url, FALSE, TRUE);

        if (isset($response['content'])) {
            $data = json_decode($response['content']);
        }
        $available = FALSE;
        if (is_array($data->features) && count($data->features)) {
            $plugins = get_plugins();
            foreach ($data->features as $feature) {
                if (!isset($plugins[$feature])) {
                    $available = TRUE;
                    break;
                }
            }
        }

        $result = array(
            'status' => 'success',
            'available' => $available
        );


        die(json_encode($result));
    }

    /*
     * Get Information about current post or page
     */

    protected function get_info() {
        global $wp_post_statuses;

        $id = intval($_POST['id']);
        $type = trim($_POST['type']);
        $role = $_POST['role'];
        $options = $this->get_blog_option(WPACCESS_PREFIX . 'restrictions', array());
        //render html
        $tmpl = new mvb_coreTemplate();
        $templatePath = WPACCESS_TEMPLATE_DIR . 'admin_options.html';
        $template = $tmpl->readTemplate($templatePath);
        $template = $tmpl->retrieveSub('POST_INFORMATION', $template);
        $result = array('status' => 'error');

        switch ($type) {
            case 'post':
                //get information about page or post
                $post = get_post($id);
                if ($post->ID) {
                    $template = $tmpl->retrieveSub('POST', $template);
                    if (isset($options[$role]['posts'][$id])) {
                        $checked = ($options[$role]['posts'][$id]['restrict'] ? 'checked' : '');
                        $checked_front = ($options[$role]['posts'][$id]['restrict_front'] ? 'checked' : '');
                        $expire = ($options[$role]['posts'][$id]['expire'] ? date('m/d/Y', $options[$role]['posts'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###post_title###' => $this->edit_post_link($post),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
                        '###restrict_front_checked###' => (isset($checked_front) ? $checked_front : ''),
                        '###restrict_expire###' => (isset($expire) ? $expire : ''),
                        '###post_type###' => ucfirst($post->post_type),
                        '###post_status###' => $wp_post_statuses[$post->post_status]->label,
                        '###post_visibility###' => $this->check_visibility($post),
                        '###ID###' => $post->ID,
                    );

                    $result = array(
                        'status' => 'success',
                        'html' => $tmpl->updateMarkers($markerArray, $template)
                    );
                }
                break;

            case 'taxonomy':
                //get information about category
                $taxonomy = $this->get_taxonomy_by_term($id);
                $term = get_term($id, $taxonomy);
                if ($term->term_id) {
                    $template = $tmpl->retrieveSub('CATEGORY', $template);
                    if (isset($options[$role]['categories'][$id])) {
                        $checked = ($options[$role]['categories'][$id]['restrict'] ? 'checked' : '');
                        $checked_front = ($options[$role]['categories'][$id]['restrict_front'] ? 'checked' : '');
                        $expire = ($options[$role]['categories'][$id]['expire'] ? date('m/d/Y', $options[$role]['categories'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###name###' => $this->edit_term_link($term),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
                        '###restrict_front_checked###' => (isset($checked_front) ? $checked_front : ''),
                        '###restrict_expire###' => (isset($expire) ? $expire : ''),
                        '###post_number###' => $term->count,
                        '###ID###' => $term->term_id,
                    );

                    $result = array(
                        'status' => 'success',
                        'html' => $tmpl->updateMarkers($markerArray, $template)
                    );
                }
                break;

            default:
                break;
        }

        die(json_encode($result));
    }

    /*
     * Save information about page/post/category restriction
     * 
     */

    protected function save_info() {
        global $upgrade_restriction;

        $options = $this->get_blog_option(WPACCESS_PREFIX . 'restrictions');
        if (!is_array($options)) {
            $options = array();
        }
        $result = array('status' => 'error');
        $id = intval($_POST['id']);
        $type = $_POST['type'];
        $role = $_POST['role'];
        $restrict = (isset($_POST['restrict']) ? 1 : 0);
        $restrict_front = (isset($_POST['restrict_front']) ? 1 : 0);
        $expire = $this->paserDate($_POST['restrict_expire']);
        $limit = apply_filters(WPACCESS_PREFIX . 'restrict_limit', WPACCESS_RESTRICTION_LIMIT);

        if ($role != WPACCESS_ADMIN_ROLE || $this->is_super) {
            switch ($type) {
                case 'post':
                    $count = 0;
                    if (!isset($options[$role]['posts'])) {
                        $options[$role]['posts'] = array();
                    } else {//calculate how many restrictions
                        foreach ($options[$role]['posts'] as $t) {
                            if ($t['restrict'] || $t['restrict_front'] || $t['expire']) {
                                $count++;
                            }
                        }
                    }
                    $c_restrict = ($restrict + $restrict_front >= 1 ? 1 : 0);

                    $no_limits = ( ($limit == -1) || ($count + $c_restrict <= $limit) ? TRUE : FALSE);
                    if ($no_limits) {
                        $options[$role]['posts'][$id] = array(
                            'restrict' => $restrict,
                            'restrict_front' => $restrict_front,
                            'expire' => $expire
                        );
                        $this->update_blog_option(WPACCESS_PREFIX . 'restrictions', $options);
                        $result = array('status' => 'success');
                    } else {
                        $result['message'] = $upgrade_restriction;
                    }
                    break;

                case 'taxonomy':
                    $count = 0;
                    if (!isset($options[$role]['categories'])) {
                        $options[$role]['categories'] = array();
                    } else {//calculate how many restrictions
                        foreach ($options[$role]['categories'] as $t) {
                            if ($t['restrict'] || $t['restrict_front'] || $t['expire']) {
                                $count++;
                            }
                        }
                    }
                    $c_restrict = ($restrict + $restrict_front >= 1 ? 1 : 0);
                    $no_limits = ( ($limit == -1) || $count + $c_restrict <= $limit ? TRUE : FALSE);
                    if ($no_limits) {
                        $options[$role]['categories'][$id] = array(
                            'restrict' => $restrict,
                            'restrict_front' => $restrict_front,
                            'expire' => $expire
                        );
                        $this->update_blog_option(WPACCESS_PREFIX . 'restrictions', $options);
                        $result = array('status' => 'success');
                    } else {
                        $result['message'] = $upgrade_restriction;
                    }
                    break;

                default:
                    break;
            }
        }

        die(json_encode($result));
    }

    protected function paserDate($date) {

        if (trim($date)) {
            $date = strtotime($date);
        }
        if ($date <= time()) {
            $date = '';
        }

        return $date;
    }

    /*
     * Get Post tree
     * 
     */

    protected function get_treeview() {
        global $wp_post_types;

        $type = $_REQUEST['root'];

        if ($type == "source") {
            $tree = array();
            if (is_array($wp_post_types)) {
                foreach ($wp_post_types as $post_type => $data) {
                    //show only list of post type which have User Interface
                    if ($data->show_ui) {
                        $tree[] = (object) array(
                                    'text' => $data->label,
                                    'expanded' => FALSE,
                                    'hasChildren' => TRUE,
                                    'id' => $post_type,
                                    'classes' => 'roots',
                        );
                    }
                }
            }
        } else {
            $parts = preg_split('/\-/', $type);

            switch (count($parts)) {
                case 1: //root of the post type
                    $tree = $this->build_branch($parts[0]);
                    break;

                case 2: //post type
                    if ($parts[0] == 'post') {
                        $post_type = get_post_field('post_type', $parts[1]);
                        $tree = $this->build_branch($post_type, FALSE, $parts[1]);
                    } elseif ($parts[0] == 'cat') {
                        $taxonomy = $this->get_taxonomy_by_term($parts[1]);
                        $tree = $this->build_branch(NULL, $taxonomy, $parts[1]);
                    }
                    break;

                default:
                    $tree = array();
                    break;
            }
        }
        die(json_encode($tree));
    }

    private function build_branch($post_type, $taxonomy = FALSE, $parent = 0) {
        global $wpdb;

        $tree = array();
        if (!$parent && !$taxonomy) { //root of a branch
            $tree = $this->build_categories($post_type);
        } elseif ($taxonomy) { //build sub categories
            $tree = $this->build_categories('', $taxonomy, $parent);
        }
        //render list of posts in current category
        if ($parent == 0) {

            $query = "SELECT p.ID FROM `{$wpdb->posts}` AS p ";
            $query .= "LEFT JOIN `{$wpdb->term_relationships}` AS r ON ( p.ID = r.object_id ) ";
            $query .= "WHERE (p.post_type = '{$post_type}') AND (p.post_status NOT IN ('trash', 'auto-draft')) AND (p.post_parent = 0) AND r.object_id IS NULL";
            $posts = $wpdb->get_col($query);
        } elseif ($parent && $taxonomy) {
            $posts = get_objects_in_term($parent, $taxonomy);
        } elseif ($post_type && $parent) {
            $posts = get_posts(array('post_parent' => $parent, 'post_type' => $post_type, 'fields' => 'ids', 'nopaging' => TRUE));
        }

        if (is_array($posts)) {
            foreach ($posts as $post_id) {
                $post = get_post($post_id);
                $onClick = "loadInfo(event, \"post\", {$post->ID});";
                $tree[] = (object) array(
                            'text' => "<a href='#' onclick='{$onClick}'>{$post->post_title}</a>",
                            'hasChildren' => $this->has_post_childs($post),
                            'classes' => 'post-ontree',
                            'id' => 'post-' . $post->ID
                );
            }
        }

        return $tree;
    }

    private function build_categories($post_type, $taxonomy = FALSE, $parent = 0) {

        $tree = array();

        if ($parent) {
            //$taxonomy = $this->get_taxonomy_get_term($parent);
            //firstly render the list of sub categories
            $cat_list = get_terms($taxonomy, array('get' => 'all', 'parent' => $parent));
            if (is_array($cat_list)) {
                foreach ($cat_list as $category) {
                    $tree[] = $this->build_category($category);
                }
            }
        } else {
            $taxonomies = get_object_taxonomies($post_type);
            foreach ($taxonomies as $taxonomy) {
                if (is_taxonomy_hierarchical($taxonomy)) {
                    $term_list = get_terms($taxonomy);
                    if (is_array($term_list)) {
                        foreach ($term_list as $term) {
                            $tree[] = $this->build_category($term);
                        }
                    }
                }
            }
        }

        return $tree;
    }

    private function get_taxonomy_by_term($term_id) {
        global $wpdb;

        $query = "SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_id = {$term_id}";

        return $wpdb->get_var($query);
    }

    private function build_category($category) {

        $onClick = "loadInfo(event, \"taxonomy\", {$category->term_id});";
        $branch = (object) array(
                    'text' => "<a href='#' onclick='{$onClick}'>{$category->name}</a>",
                    'expanded' => FALSE,
                    'classes' => 'important',
        );
        if ($this->has_category_childs($category)) {
            $branch->hasChildren = TRUE;
            $branch->id = "cat-{$category->term_id}";
        }

        return $branch;
    }

    /*
     * Check if category has children
     * 
     * @param int category ID
     * @return bool TRUE if has
     */

    protected function has_post_childs($post) {

        $posts = get_posts(array('post_parent' => $post->ID, 'post_type' => $post->post_type));

        return (count($posts) ? TRUE : FALSE);
    }

    /*
     * Check if category has children
     * 
     * @param int category ID
     * @return bool TRUE if has
     */

    protected function has_category_childs($cat) {
        global $wpdb;

        //get number of categories
        $query = "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE parent={$cat->term_id}";
        $counter = $wpdb->get_var($query) + $cat->count;

        return ($counter ? TRUE : FALSE);
    }

    /*
     * Add New Capability
     * 
     */

    protected function add_capability() {
        global $wpdb;

        $cap = strtolower(trim($_POST['cap']));

        if ($cap) {
            $cap = str_replace(array(' ', "'", '"'), array('_', '', ''), $cap);
            $capList = $this->user->getAllCaps();

            if (!isset($capList[$cap])) { //create new capability
                $roles = $this->get_blog_option('user_roles');
                $roles[WPACCESS_ADMIN_ROLE]['capabilities'][$cap] = 1; //add this role for admin automatically
                $this->update_blog_option('user_roles', $roles);
                //save this capability as custom created
                $custom_caps = $this->get_blog_option(WPACCESS_PREFIX . 'custom_caps');
                if (!is_array($custom_caps)) {
                    $custom_caps = array();
                }
                $custom_caps[] = $cap;
                $this->update_blog_option(WPACCESS_PREFIX . 'custom_caps', $custom_caps);
                //render html
                $tmpl = new mvb_coreTemplate();
                $templatePath = WPACCESS_TEMPLATE_DIR . 'admin_options.html';
                $template = $tmpl->readTemplate($templatePath);
                $listTemplate = $tmpl->retrieveSub('CAPABILITY_LIST', $template);
                $itemTemplate = $tmpl->retrieveSub('CAPABILITY_ITEM', $listTemplate);
                $markers = array(
                    '###role###' => $_POST['role'],
                    '###title###' => $cap,
                    '###description###' => '',
                    '###checked###' => 'checked',
                    '###cap_name###' => $this->user->getCapabilityHumanTitle($cap)
                );
                $titem = $tmpl->updateMarkers($markers, $itemTemplate);
                $titem = $tmpl->replaceSub('CAPABILITY_DELETE', $tmpl->retrieveSub('CAPABILITY_DELETE', $titem), $titem);

                $result = array(
                    'status' => 'success',
                    'html' => $titem
                );
            } else {
                $result = array(
                    'status' => 'error',
                    'message' => 'Capability ' . $_POST['cap'] . ' already exists'
                );
            }
        } else {
            $result = array(
                'status' => 'error',
                'message' => 'Empty Capability'
            );
        }

        die(json_encode($result));
    }

    /*
     * Delete capability
     */

    protected function delete_capability() {
        global $wpdb;

        $cap = trim($_POST['cap']);
        $custom_caps = $this->get_blog_option(WPACCESS_PREFIX . 'custom_caps');

        if (in_array($cap, $custom_caps)) {
            $roles = $this->get_blog_option('user_roles');
            if (is_array($roles)) {
                foreach ($roles as &$role) {
                    if (isset($role['capabilities'][$cap])) {
                        unset($role['capabilities'][$cap]);
                    }
                }
            }
            $this->update_blog_option('user_roles', $roles);
            $result = array(
                'status' => 'success'
            );
        } else {
            $result = array(
                'status' => 'error',
                'message' => 'Current Capability can not be deleted'
            );
        }

        die(json_encode($result));
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
        $or_roles = $this->get_blog_option(WPACCESS_PREFIX . 'original_user_roles');
        $roles = $this->get_blog_option('user_roles');
        $options = $this->get_blog_option(WPACCESS_PREFIX . 'options');

        if (isset($or_roles[$role]) && isset($roles[$role]) && ($role != WPACCESS_ADMIN_ROLE || $this->is_super)) {
            $roles[$role] = $or_roles[$role];
            //save current setting to DB
            $this->update_blog_option('user_roles', $roles);
            //unset all option with metaboxes and menu
            unset($options[$role]);
            $this->update_blog_option(WPACCESS_PREFIX . 'options', $options);

            $result = array('status' => 'success');
        } else {
            $result = array('status' => 'error');
        }

        die(json_encode($result));
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

    protected function initiate_wm() {
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
        // array_unshift($typeList, 'dashboard');
        $typeQuant = count($typeList) + 1;
        $i = 0;
        if ($next) { //if next present, means that process continuing
            while ($typeList[$i] != $next) { //find post type
                $i++;
            }
            $current = $next;
            if (isset($typeList[$i + 1])) { //continue the initialization process?
                $next = $typeList[$i + 1];
            } else {
                $next = FALSE;
            }
        } else { //this is the beggining
            $current = 'dashboard';
            $next = isset($typeList[0]) ? $typeList[0] : '';
        }
        if ($current == 'dashboard') {
            $url = add_query_arg('grab', 'metaboxes', admin_url('index.php'));
        } else {
            $url = add_query_arg('grab', 'metaboxes', admin_url('post-new.php?post_type=' . $current));
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

    protected function initiate_url() {

        check_ajax_referer(WPACCESS_PREFIX . 'ajax');

        $url = $_POST['url'];
        if ($url) {
            $url = add_query_arg('grab', 'metaboxes', $url);
            $result = $this->cURL($url);
        } else {
            $result = array('status' => 'error');
        }

        die(json_encode($result));
    }

    /*
     * Render metabox list after initialization
     * 
     * Part of AJAX interface. Is used for rendering the list of initialized
     * metaboxes.
     * 
     * @return string HTML string with result
     */

    protected function render_metabox_list() {

        $m = new module_optionManager($this, $_POST['role']);
        die($m->renderMetaboxList($m->getTemplate()));
    }

    protected function create_role() {

        $m = new module_Roles();
        $result = $m->createNewRole($_POST['role']);
        if ($result['result'] == 'success') {
            $m = new module_optionManager($this, $result['new_role']);
            $result['html'] = $m->renderDeleteRoleItem($result['new_role'], array('name' => $_POST['role']));
        }

        die(json_encode($result));
    }

    protected function delete_role() {

        $m = new module_Roles();
        $m->remove_role($_POST['role']);
        die();
    }

    /*
     * ===============================================================
     *   ******************* PRIVATE METHODS ************************
     * ===============================================================
     */

    /*
     * Initialize the list of all key parameters in the list of all
     * menus and submenus.
     * 
     * This is VERY IMPORTANT step for custom links like on Magic Field or
     * E-Commerce. 
     */

    private function init_key_params() {
        global $menu, $submenu;

        $roles = $this->user->getCurrentUserRole();
        $keys = array('post_type' => 1, 'page' => 1); //add core params
        if (in_array(WPACCESS_ADMIN_ROLE, $roles)) { //do this only for admin role
            if (is_array($menu)) { //main menu
                foreach ($menu as $item) {
                    $keys = array_merge($keys, $this->get_parts($item[2]));
                }
            }
            if (is_array($submenu)) {
                foreach ($submenu as $m => $s_items) {
                    if (is_array($s_items)) {
                        foreach ($s_items as $item) {
                            $keys = array_merge($keys, $this->get_parts($item[2]));
                        }
                    }
                }
            }
            $this->update_blog_option(WPACCESS_PREFIX . 'key_params', $keys);
        }
    }

    private function filter_top_bar(&$menu, $level = 0) {

        if ($level > 999) {
            return; //save step
        }

        if (is_object($menu)) {
            foreach ($menu as $item => &$data) {
                if (isset($data['href']) && !isset($data['children']) && !$this->menu->checkAccess($data['href'])) {
                    unset($menu->{$item});
                } elseif (isset($data['children'])) {
                    $this->filter_top_bar($data['children'], $level + 1);
                    if (count($data['children'])) {
                        foreach ($data['children'] as $key => $value) {
                            $data['href'] = $value['href'];
                            break;
                        }
                    } else {
                        unset($menu[$item]);
                    }
                }
            }
        }
    }

    private function get_parts($menu) {

        //splite requested URI
        $parts = preg_split('/\?/', $menu);
        $result = array();

        if (count($parts) > 1) { //no parameters
            $params = preg_split('/&|&amp;/', $parts[1]);
            foreach ($params as $param) {
                $t = preg_split('/=/', $param);
                $result[trim($t[0])] = 1;
            }
        }

        return $result;
    }

    private function check_visibility($post) {
        global $wp_post_statuses;

        if (!empty($post->post_password)) {
            $visibility = 'Password Protected';
        } elseif ($post->post_status == 'private') {
            $visibility = $wp_post_statuses['private']->label;
        } else {
            $visibility = 'Public';
        }

        return $visibility;
    }

    private function edit_post_link($post) {

        if (!$url = get_edit_post_link($post->ID))
            return;

        $st = $this->short_title($post->post_title);
        $link = '<a class="post-edit-link" href="' . $url . '" target="_blank" title="' . esc_attr($post->post_title) . '">' . $st . '</a>';

        return $link;
    }

    private function short_title($title) {
        //TODO - not the best way
        if (strlen($title) > 35) {
            $title = substr($title, 0, 35) . '...';
        }

        return $title;
    }

    /*
     * Get current blog ID
     * 
     * Grab current blog ID if multisite
     * 
     * @return int Current Blog ID
     */

    static public function get_current_blog() {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->blogs}";
        $sites = $wpdb->get_results($query);
        $result = FALSE;

        if (is_array($sites) && count($sites)) {
            $current = (isset($_GET['site']) ? $_GET['site'] : get_current_blog_id());
            foreach ($sites as $site) {
                if ($site->blog_id == $current) {
                    $result = $site->blog_id;
                    //get url to current blog
                    $blog_prefix = $wpdb->get_blog_prefix($site->blog_id);
                    //get Site Name
                    $query = "SELECT option_value FROM {$blog_prefix}options WHERE option_name = 'siteurl'";
                    $result = array(
                        'id' => $site->blog_id,
                        'url' => $wpdb->get_var($query),
                        'prefix' => $blog_prefix
                    );
                }
            }
        } else {
            $result = array(
                'id' => 0,
                'url' => site_url(),
                'prefix' => $wpdb->prefix
            );
        }

        return $result;
    }

    /*
     * Initiate HTTP request
     * 
     * @param string Requested URL
     * @param bool Wheather send cookies or not
     * @param bool Return content or not
     * @return bool Always return TRUE
     */

    private function cURL($url, $send_cookies = TRUE, $return_content = FALSE) {
        $header = array(
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']
        );

        $cookies = array();
        if (is_array($_COOKIE) && $send_cookies) {
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
            if ($return_content) {
                $result['content'] = $res['body'];
            }
        }

        return $result;
    }

}

register_activation_hook(__FILE__, array('mvb_WPAccess', 'activate'));
register_deactivation_hook(__FILE__, array('mvb_WPAccess', 'deactivate'));
add_action('init', 'init_wpaccess');
?>