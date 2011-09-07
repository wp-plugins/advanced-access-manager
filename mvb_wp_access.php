<?php

/*
  Plugin Name: Advanced Access Manager
  Description: Manage user roles and capabilities
  Version: 1.2.1
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
     * @access protected
     */
    protected $user;

    /*
     * Module Roles
     * 
     * @var object
     * @access protected
     */
    protected $roles;

    function __construct() {
        global $post;

        //initiate some options
        $this->restrictions = $this->getRestrictions();
        $this->user = new module_User();
        $this->roles = new module_Roles();

        if (is_admin()) {

            if (isset($_GET['page']) && ($_GET['page'] == 'wp_access')) {
                parent::__construct(WPACCESS_BASE_URL, WP_PLUGIN_DIR);
                //css
                wp_enqueue_style('jquery-ui', WPACCESS_CSS_URL . 'ui/jquery-ui-1.8.16.custom.css');
                wp_enqueue_style('wpaccess-style', WPACCESS_CSS_URL . 'wpaccess_style.css');
                wp_enqueue_style('wpaccess-treeview', WPACCESS_CSS_URL . 'treeview/jquery.treeview.css');
            }

            /*
             * Configure Plugin Environmnet
             */
            add_action('admin_menu', array($this, 'admin_menu'), 999);
            add_action('wp_print_scripts', array($this, 'wp_print_scripts'), 1);
            add_action('admin_action_render_rolelist', array($this, 'render_rolelist'));
            //Add Capabilities WP core forgot to
            add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);

            //help filter
            add_filter('contextual_help', array($this, 'contextual_help'), 10, 3);

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
        } else {
            add_action('wp', array($this, 'wp_front'));
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

    public function wp_front($wp) {
        global $post, $page, $wp_query;

        if (!$wp_query->is_home() && $post) {
            switch ($post->post_type) {
                case 'post':
                case 'page':
                    $this->checkPostAccess($post->ID);
                    break;

                default:
                    //TODO - implement a hook
                    break;
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

    public function pre_get_posts($query) {

        $user_roles = $this->user->getCurrentUserRole();
        if (!count($user_roles)) { //apply restriction of all registered roles
            $user_roles = $this->getAllRoles();
        }
        $r_cats = array();
        $r_posts = array();
        foreach ($user_roles as $role) {
            if (!isset($this->restrictions[$role])) {
                continue;
            }
            if (isset($this->restrictions[$role]['categories']) && is_array($this->restrictions[$role]['categories'])) {
                $r_cats = array_merge($r_cats, array_keys($this->restrictions[$role]['categories']));
            }
            if (isset($this->restrictions[$role]['posts']) && is_array($this->restrictions[$role]['posts'])) {
                $r_posts = array_merge($r_posts, array_keys($this->restrictions[$role]['posts']));
            }
        }
        $query->query_vars['category__not_in'] = $r_cats;
        $query->query_vars['post__not_in'] = $r_posts;
        //   debug($query->query_vars);
    }

    public function get_terms($terms, $taxonomies, $args) {

        if (is_array($terms) && !$this->skip_filtering) {
            foreach ($terms as $i => $term) {
                if (($term->taxonomy == 'category') && !$this->checkRestriction('category', $term->term_id)) {
                    unset($terms[$i]);
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

        $currentOptions = get_option(WPACCESS_PREFIX . 'options');

        /*
         * Check if this is a process of initialization the metaboxes.
         * This process starts when admin click on "Refresh List" or "Initialize list"
         * on User->Access Manager page
         */

        if (isset($_GET['grab']) && ($_GET['grab'] == 'metaboxes')) {
            if (!is_array($currentOptions['settings']['metaboxes'][$post_type])) {
                $currentOptions['settings']['metaboxes'][$post_type] = array();
            }

            if (is_array($wp_meta_boxes[$post_type])) {
                $currentOptions['settings']['metaboxes'][$post_type] = array_merge($currentOptions['settings']['metaboxes'][$post_type], $wp_meta_boxes[$post_type]);
                update_option(WPACCESS_PREFIX . 'options', $currentOptions);
            }
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

    public function activate() {
        global $wpdb;

        //get current roles settings
        $roles = get_option($wpdb->prefix . 'user_roles');
        //save current setting to DB
        update_option(WPACCESS_PREFIX . 'original_user_roles', $roles);
        //add options
        $m = new module_Roles();
        $roles = (is_array($m->roles) ? array_keys($m->roles) : array());
        $options = array();
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($role == WPACCESS_ADMIN_ROLE) {
                    continue;
                }
                $options[$role] = array(
                    'menu' => array(),
                    'metaboxes' => array(),
                    'capabilities' => $m->roles[$role]['capabilities']
                );
            }
        }
        update_option(WPACCESS_PREFIX . 'options', $options);

        //add custom capabilities
        $custom_caps = get_option(WPACCESS_PREFIX . 'custom_caps');
        if (!is_array($custom_caps)) {
            $custom_caps = array();
        }
        $custom_caps[] = 'edit_comment';
        update_option(WPACCESS_PREFIX . 'custom_caps', $custom_caps);
        $roles = get_option($wpdb->prefix . 'user_roles');
        $roles[WPACCESS_ADMIN_ROLE]['capabilities']['edit_comment'] = 1; //add this role for admin automatically
        update_option($wpdb->prefix . 'user_roles', $roles);
    }

    /*
     * Deactivation hook
     * 
     * Delete all record in DB related to current plugin
     * Restore original user roles
     */

    public function deactivate() {

        $roles = get_option(WPACCESS_PREFIX . 'original_user_roles');

        if (is_array($roles) && count($roles)) {
            update_option($wpdb->prefix . 'user_roles', $roles);
        }
        delete_option(WPACCESS_PREFIX . 'original_user_roles');
        delete_option(WPACCESS_PREFIX . 'options');
        delete_option(WPACCESS_PREFIX . 'restrictions');
        delete_option(WPACCESS_PREFIX . 'menu_order');
    }

    /*
     * Print general JS files and localization
     * 
     */

    public function wp_print_scripts() {
        if (isset($_GET['page']) && ($_GET['page'] == 'wp_access')) {
            parent::scripts();
            wp_enqueue_script('jquery-ui', WPACCESS_JS_URL . 'ui/jquery-ui.min.js');
            wp_enqueue_script('jquery-treeview', WPACCESS_JS_URL . 'treeview/jquery.treeview.js');
            wp_enqueue_script('jquery-cookie', WPACCESS_JS_URL . 'treeview/jquery.cookie.js');
            wp_enqueue_script('jquery-treeedit', WPACCESS_JS_URL . 'treeview/jquery.treeview.edit.js');
            wp_enqueue_script('jquery-treeview-ajax', WPACCESS_JS_URL . 'treeview/jquery.treeview.async.js');
            wp_enqueue_script('wpaccess-admin', WPACCESS_JS_URL . 'admin-options.js');
            wp_enqueue_script('jquery-tooltip', WPACCESS_JS_URL . 'jquery.tools.min.js');
            wp_localize_script('wpaccess-admin', 'wpaccessLocal', array(
                'handlerURL' => site_url() . '/wp-admin/index.php', //can't use admin-ajax.php in fact it doesn't load menu and submenu
                'nonce' => wp_create_nonce(WPACCESS_PREFIX . 'ajax')
            ));
        }
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

        if (is_admin()) {
            $uri = $_SERVER['REQUEST_URI'];
            $m = new module_filterMenu();

            //TODO - Move this action to checkAcess
            $access = $m->checkAccess($uri);
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

            if ($post_id) { //check if current user has access to current post
                $this->checkPostAccess($post_id);
            } elseif (isset($_GET['taxonomy']) && isset($_GET['tag_ID'])) { // TODO - Find better way  
                if ($_GET['taxonomy'] == 'category') {
                    $cat_obj = get_term($_GET['tag_ID'], 'category');
                    if (!$this->checkRestriction('category', $cat_obj->term_id)) {
                        wp_die($restrict_message);
                    }
                }
            }
        } else {
            if (is_category()) {
                $cat_obj = $wp_query->get_queried_object();
                if (!$this->checkRestriction('category', $cat_obj->term_id)) {
                    wp_redirect(home_url());
                    die();
                }
            } else {
                //leave rest for "wp" action
            }
        }
    }

    /*
     * Check if user has access to current post
     * 
     * @param int Post ID
     */

    protected function checkPostAccess($post_id) {
        global $restrict_message, $post;

        $user_roles = $this->user->getCurrentUserRole();

        if (!count($user_roles)) {
            $user_roles = $this->getAllRoles();
        }
        //get post's categories
        $c_post = wp_get_post_categories($post_id);
        $c_post = (is_array($c_post) ? $c_post : array());

        while (list($i, $role) = each($user_roles)) {
            $r_info = $this->restrictions[$role];
            if (isset($r_info['categories']) && is_array($r_info['categories'])) {//check if no restriction on categoris
                $c_list = array_keys($r_info['categories']);
                if (count(array_intersect($c_post, $c_list))) {
                    wp_die($restrict_message);
                }
            }

            if (isset($r_info['posts']) && is_array($r_info['posts'])) {
                if (isset($r_info['posts'][$post_id]) && $this->checkExpiration($r_info['posts'][$post_id])) {
                    wp_die($restrict_message);
                }
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
        add_submenu_page('users.php', __('Access Manager'), __('Access Manager'), WPACCESS_ADMIN_ROLE, 'wp_access', array($this, 'manager_page'));

        //filter the menu
        $m = new module_filterMenu();
        $m->manage();
    }

    /*
     * Option page renderer
     */

    public function manager_page() {

        $c_role = (isset($_POST['current_role']) ? $_POST['current_role'] : FALSE);
        $m = new module_optionManager($c_role);
        $m->manage();
    }

    public function render_rolelist() {

        $m = new module_optionManager($_POST['role']);
        $or_roles = get_option(WPACCESS_PREFIX . 'original_user_roles');
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

    protected function getRestrictions() {

        $rests = get_option(WPACCESS_PREFIX . 'restrictions');
        if (!is_array($rests)) {
            $rests = array();
        }

        /*
         * Prepare list of all categories and subcategories
         * Why is this dynamically?
         * This part initiates each time because you can reogranize the
         * category tree after appling restiction to category
         */
        $this->skip_filtering = TRUE;
        foreach ($rests as $role => $data) {
            if (is_array($data['categories'])) {
                foreach ($data['categories'] as $cat_id => &$restrict) {
                    //now check combination of options
                    if ($this->checkExpiration($restrict)) {
                        $restrict['restrict'] = 1;
                        //get list of all subcategories
                        $cat_list = get_term_children($cat_id, 'category');
                        while (list($t, $cid) = each($cat_list)) {
                            $rests[$role]['categories'][$cid] = $restrict;
                        }
                    } else {
                        unset($rests[$role]['categories'][$cat_id]);
                    }
                }
            }
            //prepare list of posts and pages
            if (isset($data['posts']) && is_array($data['posts'])) {
                foreach ($data['posts'] as $post_id => &$restrict) {
                    //now check combination of options
                    if ($this->checkExpiration($restrict)) {
                        $restrict['restrict'] = 1;
                    } else {
                        unset($rests[$role]['posts'][$post_id]);
                    }
                }
            }
        }
        $this->skip_filtering = FALSE;

        return $rests;
    }

    protected function checkExpiration($data) {

        $result = 0;
        if ($data['restrict'] && !trim($data['expire'])) {
            $result = 1;
        } elseif ($data['restrict'] && trim($data['expire'])) {
            if ($data['expire'] >= time()) {
                $result = 1;
            }
        } elseif (trim($data['expire'])) {
            if (time() <= $data['expire']) {
                $result = 1;
            }
        }

        return $result;
    }

    protected function getAllRoles() {

        $roles = (is_array($this->roles->roles) ? array_keys($this->roles->roles) : array());

        return $roles;
    }

    protected function checkRestriction($type, $id) {

        $allowed = TRUE;
        $user_roles = $this->user->getCurrentUserRole();
        if (!count($user_roles)) { //apply restriction of all registered roles
            $user_roles = $this->getAllRoles();
        }

        switch ($type) {
            case 'category':
                if (is_array($user_roles)) {
                    foreach ($user_roles as $role) {
                        if (isset($this->restrictions[$role]['categories'][$id])) {
                            $allowed = ($this->restrictions[$role]['categories'][$id]['restrict'] ? FALSE : TRUE);
                            break;
                        }
                    }
                }
                break;

            case 'post':
            case 'page':
                if (is_array($user_roles)) {
                    foreach ($user_roles as $role) {
                        if (isset($this->restrictions[$role]['posts'][$id])) {
                            $allowed = ($this->checkExpiration($this->restrictions[$role]['posts'][$id]) ? FALSE : TRUE);
                            break;
                        }
                    }
                }
                break;

            default:
                break;
        }

        return $allowed;
    }

    /*
     * Save menu order
     * 
     */

    protected function save_order() {

        $apply_all = $_POST['apply_all'];
        $menu_order = get_option(WPACCESS_PREFIX . 'menu_order');
        if ($apply_all) {
            $roles = $this->getAllRoles();
            foreach ($roles as $role) {
                if ($role == WPACCESS_ADMIN_ROLE) {
                    continue;
                }
                $menu_order[$role] = $_POST['menu'];
            }
        } elseif ($_POST['role'] != WPACCESS_ADMIN_ROLE) {
            $menu_order[$_POST['role']] = $_POST['menu'];
        }

        update_option(WPACCESS_PREFIX . 'menu_order', $menu_order);

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
        $options = get_option(WPACCESS_PREFIX . 'restrictions');
        //render html
        $tmpl = new mvb_coreTemplate();
        $templatePath = WPACCESS_TEMPLATE_DIR . 'admin_options.html';
        $template = $tmpl->readTemplate($templatePath);
        $template = $tmpl->retrieveSub('POST_INFORMATION', $template);
        $result = array('status' => 'error');

        switch ($type) {
            case 'post':
            case 'page':
                //get information about page or post
                $post = get_post($id);
                if ($post->ID) {
                    $template = $tmpl->retrieveSub('POST', $template);
                    if (isset($options[$role]['posts'][$id])) {
                        $checked = ($options[$role]['posts'][$id]['restrict'] ? 'checked' : '');
                        $expire = ($options[$role]['posts'][$id]['expire'] ? date('m/d/Y', $options[$role]['posts'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###post_title###' => $this->edit_post_link($post),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
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

            case 'category':
                //get information about category
                $term = get_term($id, $type);
                if ($term->term_id) {
                    $template = $tmpl->retrieveSub('CATEGORY', $template);
                    if (isset($options[$role]['categories'][$id])) {
                        $checked = ($options[$role]['categories'][$id]['restrict'] ? 'checked' : '');
                        $expire = ($options[$role]['categories'][$id]['expire'] ? date('m/d/Y', $options[$role]['categories'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###name###' => $this->edit_term_link($term),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
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

        $options = get_option(WPACCESS_PREFIX . 'restrictions');
        if (!is_array($options)) {
            $options = array();
        }
        $result = array('status' => 'error');
        $id = intval($_POST['id']);
        $type = $_POST['type'];
        $role = $_POST['role'];
        $restrict = (isset($_POST['restrict']) ? 1 : 0);
        $expire = $this->paserDate($_POST['restrict_expire']);
        $limit = apply_filters(WPACCESS_PREFIX . 'restrict_limit', WPACCESS_RESTRICTION_LIMIT);

        if ($role != WPACCESS_ADMIN_ROLE) {
            switch ($type) {
                case 'post':
                case 'page':
                    $count = 0;
                    if (!isset($options[$role]['posts'])) {
                        $options[$role]['posts'] = array();
                    } else {//calculate how many restrictions
                        foreach ($options[$role]['posts'] as $t) {
                            if ($t['restrict'] || $t['expire']) {
                                $count++;
                            }
                        }
                    }

                    $no_limits = ( ($limit == -1) || ($count + $restrict <= $limit) ? TRUE : FALSE);
                    if ($no_limits) {
                        $options[$role]['posts'][$id] = array(
                            'restrict' => $restrict,
                            'expire' => $expire
                        );
                        update_option(WPACCESS_PREFIX . 'restrictions', $options);
                        $result = array('status' => 'success');
                    } else {
                        $result['message'] = $upgrade_restriction;
                    }
                    break;

                case 'category':
                    $count = 0;
                    if (!isset($options[$role]['categories'])) {
                        $options[$role]['categories'] = array();
                    } else {//calculate how many restrictions
                        foreach ($options[$role]['categories'] as $t) {
                            if ($t['restrict'] || $t['expire']) {
                                $count++;
                            }
                        }
                    }
                    $no_limits = ( ($limit == -1) || $count + $restrict <= $limit ? TRUE : FALSE);
                    if ($no_limits) {
                        $options[$role]['categories'][$id] = array(
                            'restrict' => $restrict,
                            'expire' => $expire
                        );
                        update_option(WPACCESS_PREFIX . 'restrictions', $options);
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

        $type = $_REQUEST['root'];

        if ($type == "source") {
            $post_branch = (object) array(
                        'text' => 'Posts',
                        'expanded' => FALSE,
                        'hasChildren' => TRUE,
                        'id' => 'post',
                        'classes' => 'roots',
            );
            $page_branch = (object) array(
                        'text' => 'Pages',
                        'expanded' => FALSE,
                        'hasChildren' => TRUE,
                        'id' => 'page',
                        'classes' => 'roots',
            );
            $tree = array($post_branch, $page_branch);
            //
        } else {
            switch ($type) {
                case 'post':
                    $tree = $this->build_post_tree(0);
                    break;

                case 'page':
                    $tree = $this->build_page_tree(0);
                    break;

                default:
                    if (strpos($type, 'post-') !== FALSE) {
                        $id = substr($type, 5);
                        $tree = $this->build_post_tree($id);
                    } elseif (strpos($type, 'page-') !== FALSE) {
                        $id = substr($type, 5);
                        $tree = $this->build_page_tree($id);
                    }
                    break;
            }
        }
        die(json_encode($tree));
    }

    /*
     * Build Category Tree
     * 
     */

    protected function build_post_tree($parent = 0) {
        $tree = array();

        $cat_list = get_terms('category', array('get' => 'all', 'parent' => $parent));
        //firstly build categories
        if (is_array($cat_list)) {
            foreach ($cat_list as $category) {
                $onClick = "loadInfo(event, \"category\", {$category->term_id});";
                $branch = (object) array(
                            'text' => "<a href='#' onclick='{$onClick}'>{$category->name}</a>",
                            'expanded' => FALSE,
                            'classes' => 'important',
                );
                if ($this->has_category_childs($category)) {
                    //$branch->children = $this->build_category_tree($category->term_id);
                    $branch->hasChildren = TRUE;
                    $branch->id = 'post-' . $category->term_id;
                }
                $tree[] = $branch;
            }
        }
        //exclude all post which are uncategories
        if ($parent != 0) {
            $cur_term = get_term($parent, 'category'); // get current term
            if ($cur_term->count) {
                //now build list of posts
                $posts = get_posts(array(
                    'category' => $parent,
                    'nopaging' => TRUE)
                );
                if (is_array($posts)) {
                    foreach ($posts as $post) {
                        $onClick = "loadInfo(event, \"post\", {$post->ID});";
                        $tree[] = (object) array(
                                    'text' => "<a href='#' onclick='{$onClick}'>{$post->post_title}</a>",
                                    'hasChildren' => FALSE,
                                    'classes' => 'post-ontree'
                        );
                    }
                }
            }
        }

        return $tree;
    }

    protected function build_page_tree($parent = 0) {

        $tree = array();
        $page_list = get_pages(array('parent' => $parent, 'child_of' => $parent, 'post_status' => array('draft', 'publish', 'private')));
        //firstly build categories
        if (is_array($page_list)) {
            foreach ($page_list as $page) {
                $onClick = "loadInfo(event, \"page\", {$page->ID});";
                $branch = (object) array(
                            'text' => "<a href='#' onclick='{$onClick}'>{$page->post_title}</a>",
                            'expanded' => FALSE,
                );
                if ($this->has_page_childs($page)) {
                    $branch->hasChildren = TRUE;
                    $branch->classes = 'important';
                    $branch->id = 'page-' . $page->ID;
                } else {
                    $branch->class = 'post-ontree';
                }
                $tree[] = $branch;
            }
        }

        return $tree;
    }

    /*
     * Check if category has children
     * 
     * @param int category ID
     * @return bool TRUE if has
     */

    protected function has_page_childs($page) {

        //get number of categories
        $pages = get_pages(array('parent' => $page->ID, 'child_of' => $page->ID, 'post_status' => array('draft', 'publish', 'private')));

        return (count($pages) ? TRUE : FALSE);
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
            $m = new module_User();
            $capList = $m->getAllCaps();

            if (!isset($capList[$cap])) { //create new capability
                $roles = get_option($wpdb->prefix . 'user_roles');
                $roles[WPACCESS_ADMIN_ROLE]['capabilities'][$cap] = 1; //add this role for admin automatically
                update_option($wpdb->prefix . 'user_roles', $roles);
                //save this capability as custom created
                $custom_caps = get_option(WPACCESS_PREFIX . 'custom_caps');
                if (!is_array($custom_caps)) {
                    $custom_caps = array();
                }
                $custom_caps[] = $cap;
                update_option(WPACCESS_PREFIX . 'custom_caps', $custom_caps);
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
                    '###cap_name###' => $m->getCapabilityHumanTitle($cap)
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
        $custom_caps = get_option(WPACCESS_PREFIX . 'custom_caps');

        if (in_array($cap, $custom_caps)) {
            $roles = get_option($wpdb->prefix . 'user_roles');
            if (is_array($roles)) {
                foreach ($roles as &$role) {
                    if (isset($role['capabilities'][$cap])) {
                        unset($role['capabilities'][$cap]);
                    }
                }
            }
            update_option($wpdb->prefix . 'user_roles', $roles);
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
        $or_roles = get_option(WPACCESS_PREFIX . 'original_user_roles');
        $roles = get_option($wpdb->prefix . 'user_roles');
        $options = get_option(WPACCESS_PREFIX . 'options');

        if (isset($or_roles[$role]) && isset($roles[$role]) && ($role != WPACCESS_ADMIN_ROLE)) {
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

        $m = new module_optionManager($_POST['role']);
        die($m->renderMetaboxList($m->getTemplate()));
    }

    protected function create_role() {

        $m = new module_Roles();
        $result = $m->createNewRole($_POST['role']);
        if ($result['result'] == 'success') {
            $m = new module_optionManager($result['new_role']);
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