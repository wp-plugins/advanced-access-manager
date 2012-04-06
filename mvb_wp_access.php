<?php

/*
  Plugin Name: Advanced Access Manager
  Description: Manage Access to WordPress Backend and Frontend.
  Version: 1.6.2
  Author: Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
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
class mvb_WPAccess {

    /**
     * Main Access Controller
     *
     * @access public
     * @var mvb_Model_AccessControl
     */
    protected $access_control;

    /**
     * Config Press
     *
     * @access protected
     * @var mvb_Model_ConfigPress
     */
    protected $config_press;

    /**
     * Initialize all necessary vars and hooks
     *
     * @return void
     */
    public function __construct() {

        $this->wp_upgrade();
        $this->initPro();
        $this->access_control = new mvb_Model_AccessControl($this);

        if (is_admin()) {
            //init labels
            mvb_Model_Label::initLabels();

            add_action('admin_print_scripts', array($this, 'admin_print_scripts'));
            add_action('admin_print_styles', array($this, 'admin_print_styles'));

            if (mvb_Model_API::isNetworkPanel()) {
                add_action('network_admin_menu', array($this, 'admin_menu'), 999);
            } else {
                add_action('admin_menu', array($this, 'admin_menu'), 999);
            }

            add_action('admin_action_render_optionlist', array($this, 'render_optionlist'));

            //Add Capabilities WP core forgot to
            add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);

            add_action('before_delete_post', array($this, 'before_delete_post'));
            add_action('trash_post', array($this, 'before_delete_post'));

            //ajax
            add_action('wp_ajax_mvbam', array($this, 'ajax'));

            add_action("do_meta_boxes", array($this, 'metaboxes'), 999, 3);
           // add_thickbox();
            //user edit
            add_action('edit_user_profile_update', array($this, 'edit_user_profile_update'));
            //roles
            add_filter('editable_roles', array($this, 'editable_roles'), 999);
        } else {
            add_action('wp_before_admin_bar_render', array($this, 'wp_before_admin_bar_render'));
            add_action('wp', array($this, 'wp_front'));
            add_filter('get_pages', array($this, 'get_pages'));
            add_filter('wp_get_nav_menu_items', array($this, 'wp_get_nav_menu_items'));
        }

        if (!mvb_Model_API::isSuperAdmin()) {
            add_filter('get_terms', array($this, 'get_terms'), 10, 3);
            add_action('pre_get_posts', array($this, 'pre_get_posts'));
        }

        add_filter('sidebars_widgets', array($this, 'sidebars_widgets'));

        //Main Hook, used to check if user is authorized to do an action
        //Executes after WordPress environment loaded and configured
        add_action('wp_loaded', array($this, 'check'), 999);
    }

    /**
     * Upgrade plugin if necessary
     *
     * @access public
     */
    public static function wp_upgrade(){

        if (!file_exists(WPACCESS_UPGRADED_FILE)){
            $blog = mvb_Model_API::getBlog(1);
            $config = mvb_Model_API::getBlogOption(
                    WPACCESS_PREFIX . 'config_press',
                    '',
                    $blog
            );
            mvb_Model_ConfigPress::saveConfig($config);

            //create dummy file that is updated
            file_put_contents(WPACCESS_UPGRADED_FILE, 'OK');
        }
    }

    protected function initPro(){
        static $pro;

        if (class_exists('mvb_Model_Pro')){
            $pro = new mvb_Model_Pro();
        }elseif($license = mvb_Model_ConfigPress::getOption('aam.license_key')){
            $url = WPACCESS_PRO_URL . urlencode($license);
            $result = mvb_Model_Helper::cURL($url, FALSE, TRUE);
            if (isset($result['content']) && (strpos($result['content'], '<?php') !== FALSE)){
                if (file_put_contents(WPACCESS_BASE_DIR . 'model/pro.php', $result['content'])){
                    $pro = new mvb_Model_Pro();
                }else{
                    trigger_error('Directory model is not writable');
                }
            }else{
                trigger_error('Request error or licence key is incorrect');
            }
        }
    }

    public function sidebars_widgets($widgets) {
        global $wp_registered_widgets;

        if (!mvb_Model_API::isSuperAdmin()){
            $m = new mvb_Model_FilterMetabox($this);
            $widgets = $m->manage('widgets', $widgets);
        }

        return $widgets;
    }

    /**
     *
     * @return type
     */
    public function getAccessControl() {

        return $this->access_control;
    }

    /**
     *
     * @param type $post_id
     */
    public function before_delete_post($post_id) {

        $post = get_post($post_id);
        if (!$this->getAccessControl()->checkPostAccess($post)) {
            mvb_Model_Helper::doRedirect();
        }
    }

    /**
     *
     * @param type $user_id
     */
    public function edit_user_profile_update($user_id) {

        mvb_Model_Cache::removeUserCache($user_id);
    }

    /**
     * Filter editible roles
     *
     * Get the highest curent User's Level (from 1 to 10) and filter all User
     * Roles which have higher Level. This is used for promotion feature
     * In fact that Administrator Role has the higherst 10th Level, this function
     * introduces the virtual 11th Level for Super Admin
     *
     * @param array $roles
     * @return array Filtered Role List
     */
    public function editable_roles($roles) {

        if (isset($roles[WPACCESS_SADMIN_ROLE])) { //super admin is level 11
            unset($roles[WPACCESS_SADMIN_ROLE]);
        }

        if (isset($roles['_visitor'])) {
            unset($roles['_visitor']);
        }

        //get user's highest Level
        $caps = $this->getAccessControl()->getUserConfig()->getUser()->getAllCaps();
        $highest = mvb_Model_Helper::getHighestUserLevel($caps);

        if ($highest < WPACCESS_TOP_LEVEL && is_array($roles)) { //filter roles
            foreach ($roles as $role => $data) {
                if ($highest < mvb_Model_Helper::getHighestUserLevel($data['capabilities'])) {
                    unset($roles[$role]);
                }
            }
        }

        return $roles;
    }

    /**
     * Print Stylesheets to the head of HTML
     *
     * @return void
     */
    public function admin_print_styles() {

        $print_common = TRUE;
        switch (mvb_Model_Helper::getParam('page')) {
            case 'wp_access':
                wp_enqueue_style('wpaccess-style', WPACCESS_CSS_URL . 'wpaccess_style.css');
                wp_enqueue_style('wpaccess-treeview', WPACCESS_CSS_URL . 'treeview/jquery.treeview.css');
                wp_enqueue_style('codemirror', WPACCESS_CSS_URL . 'codemirror/codemirror.css');
                wp_enqueue_style('jquery-ui', WPACCESS_CSS_URL . 'ui/jquery-ui.css');
                wp_enqueue_style( 'thickbox' );
                break;

            case 'awm-group':
                wp_enqueue_style('awm-group-style', 'http://whimba.org/public/awm-group/awm_about.css');
                wp_enqueue_style('awm-group-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery.ui.all.css');
                break;

            default:
                $print_common = FALSE;
                break;
        }

        if ($print_common) {
            //core styles
            wp_enqueue_style('dashboard');
            wp_enqueue_style('global');
            wp_enqueue_style('wp-admin');
        }
    }

    public function admin_print_scripts() {

        $print_common = TRUE;
        switch (mvb_Model_Helper::getParam('page')) {
            case 'wp_access':
                wp_enqueue_script('jquery-treeview', WPACCESS_JS_URL . 'treeview/jquery.treeview.js');
                wp_enqueue_script('jquery-treeedit', WPACCESS_JS_URL . 'treeview/jquery.treeview.edit.js');
                wp_enqueue_script('jquery-treeview-ajax', WPACCESS_JS_URL . 'treeview/jquery.treeview.async.js');
                wp_enqueue_script('wpaccess-admin', WPACCESS_JS_URL . 'admin-options.js');
                wp_enqueue_script('codemirror', WPACCESS_JS_URL . 'codemirror/codemirror.js');
                wp_enqueue_script('codemirror-xml', WPACCESS_JS_URL . 'codemirror/ini.js');
                wp_enqueue_script( 'thickbox' );
                $locals = array(
                    'nonce' => wp_create_nonce(WPACCESS_PREFIX . 'ajax'),
                    'css' => WPACCESS_CSS_URL,
                    'js' => WPACCESS_JS_URL,
                    'hide_apply_all' => mvb_Model_API::getBlogOption(WPACCESS_PREFIX . 'hide_apply_all', 0),
                    'LABEL_129' => mvb_Model_Label::get('LABEL_129'),
                    'LABEL_130' => mvb_Model_Label::get('LABEL_130'),
                    'LABEL_131' => mvb_Model_Label::get('LABEL_131'),
                    'LABEL_76' => mvb_Model_Label::get('LABEL_76'),
                    'LABEL_77' => mvb_Model_Label::get('LABEL_77'),
                    'LABEL_132' => mvb_Model_Label::get('LABEL_132'),
                    'LABEL_133' => mvb_Model_Label::get('LABEL_133'),
                    'LABEL_90' => mvb_Model_Label::get('LABEL_90'),
                    'LABEL_134' => mvb_Model_Label::get('LABEL_134'),
                    'LABEL_135' => mvb_Model_Label::get('LABEL_135'),
                    'LABEL_136' => mvb_Model_Label::get('LABEL_136'),
                    'LABEL_137' => mvb_Model_Label::get('LABEL_137'),
                    'LABEL_138' => mvb_Model_Label::get('LABEL_138'),
                    'LABEL_24' => mvb_Model_Label::get('LABEL_24'),
                    'LABEL_141' => mvb_Model_Label::get('LABEL_141'),
                    'LABEL_142' => mvb_Model_Label::get('LABEL_142'),
                    'LABEL_143' => mvb_Model_Label::get('LABEL_143'),
                );

                if (mvb_Model_API::isNetworkPanel()) {
                    //can't use admin-ajax.php in fact it doesn't load menu and submenu
                    $blog_id = (isset($_GET['site']) ? $_GET['site'] : get_current_blog_id());
                    $c_blog = mvb_Model_API::getBlog($blog_id);
                    $locals['handlerURL'] = get_admin_url($c_blog->getID(), 'index.php');
                    $locals['ajaxurl'] = get_admin_url($c_blog->getID(), 'admin-ajax.php');
                    wp_enqueue_script('wpaccess-admin-multisite', WPACCESS_JS_URL . 'admin-multisite.js');
                    wp_enqueue_script('wpaccess-admin-url', WPACCESS_JS_URL . 'jquery.url.js');
                } else {
                    $locals['handlerURL'] = admin_url('index.php');
                    $locals['ajaxurl'] = admin_url('admin-ajax.php');
                }
                //

                $answer = mvb_Model_API::getBlogOption(WPACCESS_FTIME_MESSAGE);
                if (!$answer) {
                    $locals['first_time'] = 1;
                }

                wp_enqueue_script('jquery-ui', WPACCESS_JS_URL . 'ui/jquery-ui.js', array('jquery'));

                wp_localize_script('wpaccess-admin', 'wpaccessLocal', $locals);
                break;

            case 'awm-group':
                wp_enqueue_script('awm-group-js', 'http://whimba.org/public/awm-group/awm_about.js');
                wp_enqueue_script('awm-group-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js');
                break;

            default:
                $print_common = FALSE;
                break;
        }

        if ($print_common) {
            //core scripts
            wp_enqueue_script('postbox');
            wp_enqueue_script('dashboard');
        }
    }

    /**
     * Control Front-End access
     *
     * @global object $post
     * @global object $wp_query
     * @param object $wp
     */
    public function wp_front($wp) {

        $this->getAccessControl()->checkAccess();
    }

    /*
     * Filter Admin Top Bar
     *
     */

    public function wp_before_admin_bar_render() {
        global $wp_admin_bar;

        if ($wp_admin_bar instanceof WP_Admin_Bar) {

            foreach ($wp_admin_bar->get_nodes() as $node) {
                if (!$this->getAccessControl()->getMenuFilter()->checkAccess($node->href)) {
                    $wp_admin_bar->remove_node($node->id);
                }
            }
        }
    }

    public function wp_get_nav_menu_items($pages) {

        if (is_array($pages)) { //filter all pages which are not allowed
            foreach ($pages as $i => $page) {
                $post = get_post($page->object_id);
                if (!$this->getAccessControl()->checkPostAccess($post)
                        || $this->getAccessControl()->checkPageExcluded($post)) {
                    unset($pages[$i]);
                }
            }
        }

        return $pages;
    }

    /*
     * Filter Front Menu
     *
     */

    public function get_pages($pages) {

        if (is_array($pages)) { //filter all pages which are not allowed
            foreach ($pages as $i => $page) {
                if (!$this->getAccessControl()->checkPostAccess($page)
                        || $this->getAccessControl()->checkPageExcluded($page)) {
                    unset($pages[$i]);
                }
            }
        }

        return $pages;
    }

    /**
     *
     * @param type $query
     */
    public function pre_get_posts($query) {

        $r_posts = array();
        $r_cats = array();
        $rests = $this->getAccessControl()->getUserConfig()->getRestrictions();
        $t_posts = array();

        if (isset($rests['categories']) && is_array($rests['categories'])) {
            foreach ($rests['categories'] as $id => $data) {
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
        if (isset($rests['posts']) && is_array($rests['posts'])) {
            //get list of all posts
            foreach ($rests['posts'] as $id => $data) {
                if (is_admin() && $data['restrict']) {
                    $t_posts[] = $id;
                } elseif (!is_admin() && $data['restrict_front']) {
                    $t_posts[] = $id;
                }
            }
            $t_posts = (is_array($t_posts) ? $t_posts : array());
            $r_posts = array_merge($r_posts, $t_posts);
        }

        if (isset($query->query_vars['tax_query'])) {
            $query->query_vars['tax_query'] = array_merge($query->query_vars['tax_query'], $r_cats);
        } else {
            $query->query_vars['tax_query'] = $r_cats;
        }
        if (isset($query->query_vars['post__not_in'])) {
            $query->query_vars['post__not_in'] = array_merge($query->query_vars['post__not_in'], $r_posts);
        } else {
            $query->query_vars['post__not_in'] = $r_posts;
        }
    }

    /**
     *
     * @param type $terms
     * @param type $taxonomies
     * @param type $args
     * @return type
     */
    public function get_terms($terms, $taxonomies, $args) {

        if (is_array($terms)) {
            foreach ($terms as $i => $term) {
                if (is_object($term)) {
                    if (!$this->getAccessControl()->checkCategoryAccess($term->term_id)) {
                        unset($terms[$i]);
                    }
                }
            }
        }

        return $terms;
    }

    /**
     *
     * @param string $caps
     * @param type $cap
     * @param type $user_id
     * @param type $args
     * @return string
     */
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

        if (mvb_Model_API::getBlogOption(WPACCESS_FTIME_MESSAGE, FALSE) !== FALSE) {
            $cap = ( mvb_Model_API::isSuperAdmin() ? WPACCESS_ADMIN_ROLE : 'aam_manage');
        } else {
            $cap = WPACCESS_ADMIN_ROLE;
        }
        if (current_user_can($cap)) {
            $m = new mvb_Model_Ajax($this);
            $m->process();
        } else {
            die(json_encode(array('status' => 'error', 'result' => 'error')));
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

        //get cache. Compatible with version previouse versions
        $cache = mvb_Model_API::getBlogOption(WPACCESS_PREFIX . 'cache', array());
        //TODO - deprecated
        if (!count($cache)) { //yeap this is new version
            $cache = mvb_Model_API::getBlogOption(WPACCESS_PREFIX . 'options', array());
        }
        /*
         * Check if this is a process of initialization the metaboxes.
         * This process starts when admin click on "Refresh List" or "Initialize list"
         * on User->Access Manager page
         */
        if (isset($_GET['grab']) && ($_GET['grab'] == 'metaboxes')) {

            if (isset($_GET['widget'])) {
                $cache['metaboxes']['widgets'] = $this->getWidgetList();
            } else {

                if (!isset($cache['metaboxes'][$post_type])) {
                    $cache['metaboxes'][$post_type] = array();
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
                                        $cache['metaboxes'][$post_type][$pos][$level][$box] = array(
                                            'id' => $data['id'],
                                            'title' => $data['title']
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            mvb_Model_API::updateBlogOption(WPACCESS_PREFIX . 'cache', $cache);
        } elseif (!mvb_Model_API::isSuperAdmin()) {
            $screen = get_current_screen();
            $m = new mvb_Model_FilterMetabox($this);
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

    protected function getWidgetList() {
        global $wp_registered_widgets;

        $list = array();
        if (is_array($wp_registered_widgets)) {
            foreach ($wp_registered_widgets as $id => $data) {
                if (isset($data['callback'][0])) {
                    $class_name = get_class($data['callback'][0]);
                } else {
                    $class_name = $id;
                }
                $list[$class_name] = array(
                    'title' => $data['name'],
                    'classname' => $class_name,
                    'description' => $data['description']
                );
            }
        }

        return $list;
    }

    /*
     * Activation hook
     *
     * Save default user settings
     */

    public function activate() {
        global $wpdb, $wp_version;

        if (version_compare($wp_version, '3.2', '<')) {
            exit(mvb_Model_Label::get('LABEL_122'));
        }

        if (phpversion() < '5.1.2') {
            exit(mvb_Model_Label::get('LABEL_123'));
        }
        //Do not go thourgh the list of sites in multisite support
        //It can cause delays for large amount of blogs
        self::setOptions();

        self::wp_upgrade();
    }

    /**
     * Set necessary options to DB for current BLOG
     *
     * @param object $blog
     */
    public static function setOptions($blog = FALSE) {

        $role_list = mvb_Model_API::getBlogOption('user_roles', array(), $blog);
        //save current setting to DB
        mvb_Model_API::updateBlogOption(
                WPACCESS_PREFIX . 'original_user_roles', $role_list, $blog
        );
    }

    /*
     * Deactivation hook
     *
     * Delete all record in DB related to current plugin
     * Restore original user roles
     */

    public function uninstall() {
        global $wpdb;

        $sites = mvb_Model_Helper::getSiteList();

        if (is_array($sites) && count($sites)) {
            foreach ($sites as $site) {
                $c_blog = new mvb_Model_Blog(array(
                            'id' => $site->blog_id,
                            'url' => get_site_url($site->blog_id),
                            'prefix' => $wpdb->get_blog_prefix($site->blog_id)
                        ));
                self::remove_options($c_blog);
                unset($c_blog);
            }
        } else {
            self::remove_options();
        }
    }

    /*
     * Remove options from DB
     *
     */

    public static function remove_options($blog = FALSE) {

        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'original_user_roles', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'options', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'cache', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'restrictions', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'menu_order', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'key_params', $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_FTIME_MESSAGE, $blog);
        mvb_Model_API::deleteBlogOption(WPACCESS_PREFIX . 'config_press', $blog);
    }

    /**
     * Main function for checking if user has access to a page
     *
     * Check if current user has access to requested page. If no, print an
     * notification
     * @global object $wp_query
     */
    public function check() {

        $this->getAccessControl()->checkAccess();
    }

    /*
     * Main function for menu filtering
     *
     * Add Access Manager submenu to User main menu and additionality filter
     * the Main Menu according to settings
     *
     */

    public function admin_menu() {
        global $submenu, $menu;

        if (mvb_Model_API::getBlogOption(WPACCESS_FTIME_MESSAGE, FALSE) !== FALSE) {
            $aam_cap = ( mvb_Model_API::isSuperAdmin() ? WPACCESS_ADMIN_ROLE : 'aam_manage');
        } else {
            $aam_cap = WPACCESS_ADMIN_ROLE;
        }

        if (!isset($submenu['awm-group'])) {
            add_menu_page(__('AWM Group', 'aam'), __('AWM Group', 'aam'), 'administrator', 'awm-group', array($this, 'awm_group'), WPACCESS_CSS_URL . 'images/active-menu.png');
        }
        add_submenu_page('awm-group', __('Access Manager', 'aam'), __('Access Manager', 'aam'), $aam_cap, 'wp_access', array($this, 'accessManagerPage'));

        //init the list of key parameters
        $this->init_key_params();
        if (!mvb_Model_API::isSuperAdmin()) {
            //filter the menu
            $this->getAccessControl()->getMenuFilter()->manage();
        }
    }

    public function awm_group() {

        $m = new mvb_Model_About();
        $m->manage();
    }

    /**
     *
     */
    public function accessManagerPage() {

        $c_role = mvb_Model_Helper::getParam('current_role', 'REQUEST');
        $c_user = mvb_Model_Helper::getParam('current_user', 'REQUEST');

        if (mvb_Model_API::isNetworkPanel()) {
            //require phpQuery
            if (!class_exists('phpQuery')) {
                require_once(WPACCESS_BASE_DIR . 'library/phpQuery/phpQuery.php');
            }
            //TODO - I don't like site
            $blog_id = (isset($_GET['site']) ? $_GET['site'] : get_current_blog_id());
            $c_blog = mvb_Model_API::getBlog($blog_id);
            $m = new mvb_Model_Manager($this, $c_role, $c_user);
            $error = $m->do_save();
            $params = array(
                'page' => 'wp_access',
                'render_mss' => 1,
                'site' => $blog_id,
                'show_message' => (isset($_POST['submited']) && is_null($error) ? 1 : 0),
                'current_role' => $c_role,
                'current_user' => $c_user
            );

            $link = get_admin_url($c_blog->getID(), 'admin.php');
            $url = add_query_arg($params, $link);
            $result = mvb_Model_Helper::cURL($url, TRUE, TRUE);
            if (isset($result['content']) && $result['content']) {
                $content = phpQuery::newDocument($result['content']);
                if ($error){
                    $content['#manager-error-message']->removeClass('message-passive');
                    $content['#manager-error-message .manager-error-text']->html($error);
                }else{
                    $content['#manager-error-message']->remove();
                }
                echo $content['#aam_wrap']->htmlOuter();
                unset($content);
            } else {
                wp_die(mvb_Model_Label::get('LABEL_145'));
            }
        } else {
            $m = new mvb_Model_Manager($this, $c_role, $c_user);
            $m->do_save();
            $m->manage();
        }
    }

    /**
     *
     */
    public function render_optionlist() {

        $role = mvb_Model_Helper::getParam('role', 'POST');
        $user = mvb_Model_Helper::getParam('user', 'POST');
        $m = new mvb_Model_ManagerAjax($this, $role, $user);

        die(json_encode($m->manage_ajax('option_list')));
    }

    /*
     * Initialize the list of all key parameters in the list of all
     * menus and submenus.
     *
     * This is VERY IMPORTANT step for custom links like on Magic Field or
     * E-Commerce.
     */

    private function init_key_params() {
        global $menu, $submenu;

        $roles = mvb_Model_API::getCurrentUser()->getRoles();
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
            mvb_Model_API::updateBlogOption(WPACCESS_PREFIX . 'key_params', $keys);
        }
    }

    /**
     *
     * @param type $menu
     * @return int
     */
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

}

register_activation_hook(__FILE__, array('mvb_WPAccess', 'activate'));
register_uninstall_hook(__FILE__, array('mvb_WPAccess', 'uninstall'));

add_action('init', 'init_wpaccess');
add_action('set_current_user', 'aam_set_current_user');

?>