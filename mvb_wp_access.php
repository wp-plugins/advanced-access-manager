<?php

/*
  Plugin Name: Advanced Access Manager
  Description: Manage Access for all User Roles to WordPress Backend and Frontend.
  Version: 1.4.3
  Author: Vasyl Martyniuk
  Author URI: http://www.whimba.org
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

require_once('mvb_config.php');

/**
 * Main Plugin Class
 * 
 * Responsible for initialization and handling user requests to Advanced Access
 * Manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */

class mvb_WPAccess extends mvb_corePlugin {
    /**
     * Holds the restriction options
     * 
     * @var array
     * @access protected
     */

    protected $restrictions;

    /**
     * Module User
     * 
     * @var object
     * @access public
     */
    public $user;

    /**
     * Is Multisite or not
     * 
     * @var bool
     * @access public
     */
    public static $allow_ms = FALSE;

    /**
     * Is Super Admin
     * 
     * If Multisite allowed and is super admin then include
     * administrator role to manage
     * 
     * @var bool
     * @access public
     */
    public $is_super = FALSE;

    /**
     * Current Blog Info
     * 
     * @var array
     * @access public
     */
    public static $current_blog;

    /**
     * Module Roles
     * 
     * @var object
     * @access protected
     */
    protected $roles;

    /**
     * Skip filter categories during initialization
     * 
     * @var bool
     * @access protected
     */
    protected $skip_filtering = TRUE;

    public function __construct() {
        global $post;


        
        // Configure Plugin Environmnet
        self::$allow_ms = $this->init_multisite();

        //TODO - Optimize this
        $this->user = new module_User($this);
        $this->roles = new module_Roles();
        $this->menu = new module_filterMenu($this);
        $this->is_super = $this->is_super_admin();

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

            //roles
            add_filter('editable_roles', array($this, 'editable_roles'), 999);
        } else {
            add_action('wp_before_admin_bar_render', array($this, 'wp_before_admin_bar_render'));
            add_action('wp', array($this, 'wp_front'));
            add_filter('get_pages', array($this, 'get_pages'));
        }

        if (!$this->is_super) {
            add_filter('get_terms', array($this, 'get_terms'), 10, 3);
            add_action('pre_get_posts', array($this, 'pre_get_posts'));
        }
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

    function get_roles($all = FALSE) {
        global $wpdb;

        $roles = $this->get_blog_option('user_roles', array());

        if (!is_array($roles)) {
            $roles = array();
        }

        if (!$all) {
            //unset super admin role
            if (isset($roles[WPACCESS_SADMIN_ROLE])) {
                unset($roles[WPACCESS_SADMIN_ROLE]);
            }

            if (!$this->is_super && isset($roles[WPACCESS_ADMIN_ROLE])) {
                //exclude Administrator from list of allowed roles
                unset($roles[WPACCESS_ADMIN_ROLE]);
            }
        }

        return $roles;
    }

    /*
     * Check if multisite is active
     * 
     * @return bool
     */

    public function is_multi() {

        return self::$allow_ms;
    }

    /*
     * Save meta data
     * 
     */

    public function save_meta($post_id, $post) {

        if (isset($_POST['access']) && is_array($_POST['access'])) {
            $admin = $_POST['access']['restrict'];
            $front = $_POST['access']['restrict_front'];
            $exclude = $_POST['access']['exclude_page'];

            $rest_list = $this->get_blog_option(WPACCESS_PREFIX . 'restrictions', array());
            $role_list = $this->get_roles();
            foreach ($role_list as $role => $dummy) {
                if (isset($rest_list[$role]['posts'][$post->ID])) {
                    $c = $rest_list[$role]['posts'][$post->ID];
                } else {
                    $c = array(
                        'restrict' => 0,
                        'restrict_front' => 0,
                        'exclude_page' => 0
                    );
                }
                $rest_list[$role]['posts'][$post->ID] = array(
                    'restrict' => (!empty($admin) ? $admin : $c['restrict']),
                    'restrict_front' => (!empty($front) ? $front : $c['restrict_front']),
                    'exclude_page' => (!empty($exclude) ? $exclude : $c['exclude_page'])
                );
            }

            $this->update_blog_option(WPACCESS_PREFIX . 'restrictions', $rest_list);
        }
    }

    /*
     * Check if user is super admin
     * 
     */

    public function is_super_admin() {

        $super = FALSE;
        if (self::$allow_ms && is_super_admin()) {
            $super = TRUE;
        } elseif (!self::$allow_ms) {
            //check if user has a rule Super Admin
            $data = get_userdata(get_current_user_id());
            $cap_val = self::$current_blog['prefix'] . 'capabilities';
            if (isset($data->{$cap_val}[WPACCESS_SADMIN_ROLE])) {
                $super = TRUE;
            } else {
                //check if answer is stored
                $answer = $this->get_blog_option(WPACCESS_FTIME_MESSAGE, 0);
                if (!$answer) {
                    $super = TRUE;
                }
            }
        }

        return $super;
    }

    /*
     * Filter editible roles
     */

    public function editable_roles($roles) {

        if (isset($roles[WPACCESS_SADMIN_ROLE])) { //super admin is level 11
            unset($roles[WPACCESS_SADMIN_ROLE]);
        }

        //get user's highest Level
        $c_roles = $this->user->getCurrentUserRole();
        $role_list = $this->get_roles(TRUE);
        $highest = 0;
        foreach ($c_roles as $role) {
            if (isset($role_list[$role])) {
                $caps = $role_list[$role]['capabilities'];
                for ($i = 0; $i <= WPACCESS_TOP_LEVEL; $i++) {
                    if (isset($caps["level_{$i}"]) && ($highest < $i)) {
                        $highest = $i;
                    }
                }
            }
        }

        if ($highest < WPACCESS_TOP_LEVEL && is_array($roles)) { //filter roles
            foreach ($roles as $role => $data) {
                if (isset($data['capabilities']['level_' . ($highest + 1)])) {
                    unset($roles[$role]);
                }
            }
        }

        return $roles;
    }

    /*
     * Hook to init session for swfupload
     * 
     */

    public function admin_print_styles() {

        wp_enqueue_style('jquery-ui', WPACCESS_CSS_URL . 'ui/jquery-ui-1.8.16.custom.css');
        wp_enqueue_style('wpaccess-style', WPACCESS_CSS_URL . 'wpaccess_style.css');
        wp_enqueue_style('wpaccess-treeview', WPACCESS_CSS_URL . 'treeview/jquery.treeview.css');
        wp_enqueue_style('codemirror', WPACCESS_CSS_URL . 'codemirror/codemirror.css');
    }

    public function wp_front($wp) {
        global $post, $page, $wp_query, $wp;

        if (!$wp_query->is_home() && $post) {
            if ($this->checkPostAccess($post) && !$this->is_super) {
                do_action(WPACCESS_PREFIX . 'front_redirect');
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
                if (($this->checkPostAccess($page) && !$this->is_super) || $this->checkPageExcluded($page)) {
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
                $t_posts = (is_array($t_posts) ? $t_posts : array());
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

    /*
     * Initiate HTTP request
     * 
     * @param string Requested URL
     * @param bool Wheather send cookies or not
     * @param bool Return content or not
     * @return bool Always return TRUE
     */

    public function cURL($url, $send_cookies = TRUE, $return_content = FALSE) {
        $header = array(
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']
        );

        $cookies = array();
        if (is_array($_COOKIE) && $send_cookies) {
            foreach ($_COOKIE as $key => $value) {
                //SKIP PHPSESSID - some servers does not like it for security reason
                if ($key == 'PHPSESSID'){
                    continue;
                }
                $cookies[] = new WP_Http_Cookie(array(
                            'name' => $key,
                            'value' => $value
                        ));
            }
        }

        $res = wp_remote_request($url, array(
            'headers' => $header,
            'cookies' => $cookies,
            'timeout' => 5)
        );
        
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

        $cap = ( $this->is_super ? 'administrator' : 'aam_manage');
        if (current_user_can($cap)) {
            $m = new module_ajax($this);
            $m->process();
        } else {
            die(json_encode(array('status' => 'error', 'result' => 'error')));
        }
    }

    public function edit_term_link($term) {

        $st = $this->short_title($term->name);
        $link = '<a href="' . get_edit_term_link($term->term_id, 'category') . '" target="_blank" title="' . esc_attr($term->name) . '">' . $st . '</a>';

        return $link;
    }

    public function edit_post_link($post) {

        if (!$url = get_edit_post_link($post->ID))
            return;

        $st = $this->short_title($post->post_title);
        $link = '<a class="post-edit-link" href="' . $url . '" target="_blank" title="' . esc_attr($post->post_title) . '">' . $st . '</a>';

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
        } elseif (!$this->is_super) {
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
        global $wpdb, $wp_version;

        if (version_compare($wp_version, '3.1', '<')) {
            exit(LABEL_122);
        }

        if (phpversion() < '5.0') {
            exit(LABEL_123);
        }

        //BECAUSE STATIC CALLING
        self::$allow_ms = (is_multisite() && is_network_admin());
        $sites = self::get_sites();

        if (is_array($sites) && count($sites)) {
            foreach ($sites as $site) {
                //get url to current blog
                $blog_prefix = $wpdb->get_blog_prefix($site->blog_id);
                self::$current_blog = array(
                    'id' => $site->blog_id,
                    'url' => get_site_url($site->blog_id),
                    'prefix' => $blog_prefix
                );
                self::set_options();
            }
        } else {
            self::$current_blog = self::get_current_blog();
            self::set_options();
        }
    }

    /*
     * Set necessary options to DB for current BLOG
     * 
     */

    public static function set_options() {

        $role_list = self::get_blog_option('user_roles');
        //save current setting to DB
        self::update_blog_option(WPACCESS_PREFIX . 'original_user_roles', $role_list);
        //add options
        $m = new module_Roles();
        $roles = (is_array($m->roles) ? array_keys($m->roles) : array());
        $options = array();
        if (is_array($roles)) {
            foreach ($roles as $role) {
                $options[$role] = array(
                    'menu' => array(),
                    'metaboxes' => array(),
                        //'capabilities' => $m->roles[$role]['capabilities']
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

        return;
    }

    /*
     * Deactivation hook
     * 
     * Delete all record in DB related to current plugin
     * Restore original user roles
     */

    public function deactivate() {
        global $wpdb;

        //BECAUSE STATIC CALLING
        self::$allow_ms = (is_multisite() && is_network_admin());

        $sites = self::get_sites();

        if (is_array($sites) && count($sites)) {
            foreach ($sites as $site) {
                //get url to current blog
                $blog_prefix = $wpdb->get_blog_prefix($site->blog_id);
                self::$current_blog = array(
                    'id' => $site->blog_id,
                    'url' => get_site_url($site->blog_id),
                    'prefix' => $blog_prefix
                );
                self::remove_options();
            }
        } else {
            self::$current_blog = self::get_current_blog();
            self::remove_options();
        }
    }

    /*
     * Remove options from DB
     * 
     */

    public static function remove_options() {

        $roles = self::get_blog_option(WPACCESS_PREFIX . 'original_user_roles');

        if (is_array($roles) && count($roles)) {
            self::update_blog_option('user_roles', $roles);
        }
        self::delete_blog_option(WPACCESS_PREFIX . 'original_user_roles');
        self::delete_blog_option(WPACCESS_PREFIX . 'options');
        self::delete_blog_option(WPACCESS_PREFIX . 'restrictions');
        self::delete_blog_option(WPACCESS_PREFIX . 'menu_order');
        self::delete_blog_option(WPACCESS_PREFIX . 'key_params');
        self::delete_blog_option(WPACCESS_PREFIX . 'sa_dialog'); //TODO - delete in version 1.5
        self::delete_blog_option(WPACCESS_FTIME_MESSAGE);

        return;
    }

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
        wp_enqueue_script('codemirror', WPACCESS_JS_URL . 'codemirror/codemirror.js');
        wp_enqueue_script('codemirror-xml', WPACCESS_JS_URL . 'codemirror/xml.js');
        $locals = array(
            'nonce' => wp_create_nonce(WPACCESS_PREFIX . 'ajax'),
            'css' => WPACCESS_CSS_URL,
            'js' => WPACCESS_JS_URL,
            'hide_apply_all' => $this->get_blog_option(WPACCESS_PREFIX . 'hide_apply_all', 0),
            'LABEL_129' => LABEL_129,
            'LABEL_130' => LABEL_130,
            'LABEL_131' => LABEL_131,
            'LABEL_76' => LABEL_76,
            'LABEL_77' => LABEL_77,
            'LABEL_132' => LABEL_132,
            'LABEL_133' => LABEL_133,
            'LABEL_90' => LABEL_90,
            'LABEL_134' => LABEL_134,
            'LABEL_135' => LABEL_135,
            'LABEL_136' => LABEL_136,
            'LABEL_137' => LABEL_137,
            'LABEL_138' => LABEL_138,
            'LABEL_139' => LABEL_139,
            'LABEL_140' => LABEL_140,
            'LABEL_24' => LABEL_24,
            'LABEL_141' => LABEL_141,
            'LABEL_142' => LABEL_142,
            'LABEL_143' => LABEL_143,
        );

        if (self::$allow_ms) {
            //can't use admin-ajax.php in fact it doesn't load menu and submenu
            $locals['handlerURL'] = get_admin_url(self::$current_blog['id'], 'index.php');
            $locals['ajaxurl'] = get_admin_url(self::$current_blog['id'], 'admin-ajax.php');
            wp_enqueue_script('wpaccess-admin-multisite', WPACCESS_JS_URL . 'admin-multisite.js');
            wp_enqueue_script('wpaccess-admin-url', WPACCESS_JS_URL . 'jquery.url.js');
        } else {
            $locals['handlerURL'] = admin_url('index.php');
            $locals['ajaxurl'] = admin_url('admin-ajax.php');
        }
        //

        $answer = $this->get_blog_option(WPACCESS_FTIME_MESSAGE);
        if (!$answer) {
            $locals['first_time'] = 1;
        }

        wp_localize_script('wpaccess-admin', 'wpaccessLocal', $locals);
    }

    public function get_taxonomy_by_term($term_id) {
        global $wpdb;

        $query = "SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_id = {$term_id}";

        return $wpdb->get_var($query);
    }

    /*
     * Get Current Blog
     */

    public function get_current_blog_data() {

        return self::$current_blog;
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

        if (is_admin() && !$this->is_super) {
            $uri = $_SERVER['REQUEST_URI'];

            $access = $this->menu->checkAccess($uri);
            //filter
            $access = apply_filters(WPACCESS_PREFIX . 'check_access', $access, $uri);

            if (!$access) {
                do_action(WPACCESS_PREFIX . 'admin_redirect');
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
                    do_action(WPACCESS_PREFIX . 'admin_redirect');
                    wp_die($restrict_message);
                }
            } elseif (isset($_GET['taxonomy']) && isset($_GET['tag_ID'])) { // TODO - Find better way
                $user_roles = $this->user->getCurrentUserRole();
                if (!count($user_roles)) {
                    $user_roles = $this->getAllRoles();
                }
                foreach ($user_roles as $role) {
                    if ($this->checkCategoryAccess($role, array($_GET['tag_ID']))) {
                        do_action(WPACCESS_PREFIX . 'admin_redirect');
                        wp_die($restrict_message);
                    }
                }
            }
        } elseif (!$this->is_super) {
            if (is_category()) {
                $cat_obj = $wp_query->get_queried_object();
                $user_roles = $this->user->getCurrentUserRole();
                if (!count($user_roles)) {
                    $user_roles = $this->getAllRoles();
                }
                foreach ($user_roles as $role) {
                    if (!$this->checkCategoryAccess($role, array($cat_obj->term_id))) {
                        do_action(WPACCESS_PREFIX . 'front_redirect');
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

        $cap = ( $this->is_super ? 'administrator' : 'aam_manage');

        add_submenu_page('users.php', __('Access Manager', 'aam'), __('Access Manager', 'aam'), $cap, 'wp_access', array($this, 'manager_page'));

        //init the list of key parameters
        $this->init_key_params();
        if (!$this->is_super) {
            //filter the menu
            $this->menu->manage();
        }
    }

    public function manager_page() {

        $c_role = isset($_REQUEST['current_role']) ? $_REQUEST['current_role'] : FALSE;
        if (self::$allow_ms) {
            if (is_array(self::$current_blog)) { //TODO -IMPLEMENT ERROR IF SITE NOT FOUND
                $m = new module_optionManager($this, $c_role);
                $m->do_save();
                $params = array(
                    'page' => 'wp_access',
                    'current_role' => $c_role,
                    'render_mss' => 1,
                    'site' => $_GET['site']
                );
                $link = get_admin_url(self::$current_blog['id'], 'users.php');
                $url = add_query_arg($params, $link);
                $result = $this->cURL($url, TRUE, TRUE);
                if (isset($result['content']) && $result['content']) {
                    $content = phpQuery::newDocument($result['content']);
                    echo $content['#aam_wrap']->htmlOuter();
                    unset($content);
                } else {
                    wp_die(LABEL_145);
                }
            }
        } else {
            $m = new module_optionManager($this, $c_role);
            $m->do_save();
            $m->manage();
        }
    }

    /*
      public function manager_page() {

      $c_role = isset($_REQUEST['current_role']) ? $_REQUEST['current_role'] : FALSE;
      $m = new module_optionManager($this, $c_role);
      $m->do_save();
      $m->manage();
      }
     */

    public function render_rolelist() {

        $m = new module_optionManager($this, $_POST['role']);
        $or_roles = $this->get_blog_option(WPACCESS_PREFIX . 'original_user_roles');
        $content = $m->getMainOptionsList();
        $content = $m->templObj->clearTemplate($content);
        $result = array(
            'html' => $content,
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
     * Add Metabox to manage access
     * 
     */

    public function add_access_metabox() {
        global $wp_post_types;

        foreach ($wp_post_types as $post_type => $data) {
            if ($data->show_ui) {
                add_action('add_meta_boxes_' . $post_type, array($this, 'render_access_metabox'), 10, 1);
            }
        }
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

    /*
     * Check if page is excluded from the menu
     */

    protected function checkPageExcluded($page) {

        $user_roles = $this->user->getCurrentUserRole();
        if (!count($user_roles)) {
            $user_roles = $this->getAllRoles();
        }
        $exclude = FALSE;

        while (list($i, $role) = each($user_roles)) {
            $r_info = (isset($this->restrictions[$role]['posts']) ? $this->restrictions[$role]['posts'] : FALSE);

            if (is_array($r_info)) {
                //check if page exists in restriction list
                if (isset($r_info[$page->ID]) && $r_info[$page->ID]['exclude_page']) {
                    $exclude = TRUE;
                    break;
                }
            }
        }

        return $exclude;
    }

    protected function getRestrictions() {

        $rests = array();

        // if (!$this->is_super) { //is super admin - no restrictions at all
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
                        if ($rests[$role]['posts'][$post_id]['exclude_page']) {
                            $rests[$role]['posts'][$post_id] = array(
                                'exclude_page' => 1
                            );
                        } else {
                            unset($rests[$role]['posts'][$post_id]);
                        }
                    }
                }
            }
        }
        $this->skip_filtering = FALSE;
        //  }


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

    public function getAllRoles() {

        $roles = (is_array($this->roles->roles) ? array_keys($this->roles->roles) : array());

        return $roles;
    }

    public function paserDate($date) {

        if (trim($date)) {
            $date = strtotime($date);
        }
        if ($date <= time()) {
            $date = '';
        }

        return $date;
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

    public function check_visibility($post) {
        global $wp_post_statuses;

        if (!empty($post->post_password)) {
            $visibility = __('Password Protected', 'aam');
        } elseif ($post->post_status == 'private') {
            $visibility = $wp_post_statuses['private']->label;
        } else {
            $visibility = __('Public', 'aam');
        }

        return $visibility;
    }

    public function short_title($title) {
        //TODO - not the best way
        if (strlen($title) > 30) {
            $title = substr($title, 0, 30) . '...';
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

        $sites = self::get_sites();

        if (is_array($sites) && count($sites)) {
            $current = (isset($_GET['site']) ? $_GET['site'] : get_current_blog_id());
            foreach ($sites as $site) {
                if ($site->blog_id == $current) {
                    $result = $site->blog_id;
                    //get url to current blog
                    $blog_prefix = $wpdb->get_blog_prefix($site->blog_id);
                    //get Site Name
                    $result = array(
                        'id' => $site->blog_id,
                        'url' => get_site_url($site->blog_id),
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
     * Get list of sites if multisite setup
     * 
     * @return mixed Array of sites of FALSE if not mulitisite setup
     */

    public static function get_sites() {
        global $wpdb;

        if (isset($wpdb->blogs)) {
            $query = "SELECT * FROM {$wpdb->blogs}";
            $sites = $wpdb->get_results($query);
        } else {
            $sites = FALSE;
        }

        return $sites;
    }

}

register_activation_hook(__FILE__, array('mvb_WPAccess', 'activate'));
register_deactivation_hook(__FILE__, array('mvb_WPAccess', 'deactivate'));
add_action('init', 'init_wpaccess');
?>