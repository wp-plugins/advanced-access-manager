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

class module_ajax {
    /*
     * Parent Object
     * 
     * Holds the main plugin object
     * 
     * @var object
     * @access public
     */

    public $pObj;

    /*
     * Requested action
     * 
     * @var string
     * @access protected
     */
    protected $action;


    /*
     * Main Constructor
     * 
     * @param object
     */

    public function __construct($pObj) {

        $this->pObj = $pObj;
        $this->action = $this->get_action();
    }

    /*
     * Process Ajax request
     * 
     */

    public function process() {

        switch ($this->action) {
            case 'restore_role':
                $result = $this->restore_role($_POST['role']);
                break;

            case 'create_role':
                $result = $this->create_role();
                break;

            case 'delete_role':
                $result = $this->delete_role();
                break;

            case 'render_metabox_list':
                $result = $this->render_metabox_list();
                break;

            case 'initiate_wm':
                $result = $this->initiate_wm();
                break;

            case 'initiate_url':
                $result = $this->initiate_url();
                break;

            case 'import_config':
                $result = $this->import_config();
                break;

            case 'add_capability':
                $result = $this->add_capability();
                break;

            case 'delete_capability':
                $result = $this->delete_capability();
                break;

            case 'get_treeview':
                $result = $this->get_treeview();
                break;

            case 'get_info':
                $result = $this->get_info();
                break;

            case 'save_info':
                $result = $this->save_info();
                break;

            case 'check_addons':
                $result = $this->check_addons();
                break;

            case 'save_order':
                $result = $this->save_order();
                break;

            case 'export':
                $result = $this->export();
                break;

            case 'upload_config':
                $result = $this->upload_config();
                break;

            case 'create_super':
                $result = $this->create_super();
                break;

            case 'update_role_name':
                $result = $this->update_role_name();
                break;

            default:
                $result = array('status' => 'error');
                break;
        }

        die(json_encode($result));
    }

    /*
     * Update Roles Label
     * 
     */

    protected function update_role_name() {

        //TODO - Here you can hack and change Super Admin and Admin Label
        //But this is not a big deal.
        $role_list = $this->pObj->get_roles(true);
        $role = $_POST['role_id'];
        $label = sanitize_title($_POST['label']);
        if (isset($role_list[$role])) {
            $role_list[$role]['name'] = ucfirst($label);
            $this->pObj->update_blog_option('user_roles', $role_list);
            $result = array('status' => 'success');
        } else {
            $result = array('status' => 'error');
        }


        return $result;
    }

    /*
     * Get current action
     * 
     * @return bool Return true if ok
     */

    protected function get_action() {

        $a = (isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : FALSE);

        return $a;
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
        $or_roles = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'original_user_roles', array());
        $roles = $this->pObj->get_roles(TRUE);

        $allow = TRUE;
        if (($role == WPACCESS_ADMIN_ROLE) && !$this->pObj->is_super) {
            $allow = FALSE;
        }

        if (isset($or_roles[$role]) && isset($roles[$role]) && $allow) {
            $roles[$role] = $or_roles[$role];

            //save current setting to DB
            $this->pObj->update_blog_option('user_roles', $roles);

            //unset all option with metaboxes and menu
            $options = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'options');
            if (isset($options[$role])) {
                unset($options[$role]);
                $this->pObj->update_blog_option(WPACCESS_PREFIX . 'options', $options);
            }

            //unset all restrictions
            $r = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'restrictions', array());
            if (isset($r[$role])) {
                unset($r[$role]);
                $this->pObj->update_blog_option(WPACCESS_PREFIX . 'restrictions', $r);
            }

            //unset menu order
            $menu_order = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'menu_order');
            if (isset($menu_order[$role])) {
                unset($menu_order[$role]);
                $this->pObj->update_blog_option(WPACCESS_PREFIX . 'menu_order', $menu_order);
            }

            $result = array('status' => 'success');
        } else {
            $result = array('status' => 'error');
        }

        return $result;
    }

    /*
     * Create a new Role
     * 
     */

    protected function create_role($role = '') {

        $m = new module_Roles();
        $new_role = ($role ? $role : $_POST['role']);
        $result = $m->createNewRole($new_role);
        if ($result['result'] == 'success') {
            $m = new module_optionManager($this->pObj, $result['new_role']);
            $result['html'] = $m->renderDeleteRoleItem($result['new_role'], array('name' => $_POST['role']));
        }

        return $result;
    }

    /*
     * Delete Role
     * 
     */

    protected function delete_role() {

        $m = new module_Roles();
        $m->remove_role($_POST['role']);
        $result = array('status' => 'success');

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

    protected function render_metabox_list() {

        $m = new module_optionManager($this->pObj, $_POST['role']);
        $result = array(
            'status' => 'success',
            'html' => $m->renderMetaboxList($m->getTemplate())
        );

        return $result;
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
        $result = $this->pObj->cURL($url);

        $result['value'] = round((($i + 1) / $typeQuant) * 100); //value for progress bar
        $result['next'] = ($next ? $next : '' ); //if empty, stop initialization

        return $result;
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
            $result = $this->pObj->cURL($url);
        } else {
            $result = array('status' => 'error');
        }

        return $result;
    }

    /*
     * Import configurations
     * 
     */

    protected function import_config() {

        $m = new module_optionManager($this->pObj);
        $result = $m->import_config();

        return $result;
    }

    /*
     * Add New Capability
     * 
     */

    protected function add_capability() {
        global $wpdb;

        $cap = strtolower(trim($_POST['cap']));

        if ($cap) {
            $cap = sanitize_title_with_dashes($cap);
            $cap = str_replace('-', '_', $cap);
            $capList = $this->pObj->user->getAllCaps();

            if (!isset($capList[$cap])) { //create new capability
                $roles = $this->pObj->get_roles(TRUE);
                if (isset($roles['super_admin'])) {
                    $roles['super_admin']['capabilities'][$cap] = 1;
                }
                $roles[WPACCESS_ADMIN_ROLE]['capabilities'][$cap] = 1; //add this role for admin automatically
                $this->pObj->update_blog_option('user_roles', $roles);
                //save this capability as custom created
                $custom_caps = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'custom_caps');
                if (!is_array($custom_caps)) {
                    $custom_caps = array();
                }
                $custom_caps[] = $cap;
                $this->pObj->update_blog_option(WPACCESS_PREFIX . 'custom_caps', $custom_caps);
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
                    '###cap_name###' => $this->pObj->user->getCapabilityHumanTitle($cap)
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

        return $result;
    }

    /*
     * Delete capability
     */

    protected function delete_capability() {
        global $wpdb;

        $cap = trim($_POST['cap']);
        $custom_caps = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'custom_caps');

        if (in_array($cap, $custom_caps)) {
            $roles = $this->pObj->get_blog_option('user_roles');
            if (is_array($roles)) {
                foreach ($roles as &$role) {
                    if (isset($role['capabilities'][$cap])) {
                        unset($role['capabilities'][$cap]);
                    }
                }
            }
            $this->pObj->update_blog_option('user_roles', $roles);
            $result = array(
                'status' => 'success'
            );
        } else {
            $result = array(
                'status' => 'error',
                'message' => 'Current Capability can not be deleted'
            );
        }

        return $result;
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
                        $taxonomy = $this->pObj->get_taxonomy_by_term($parts[1]);
                        $tree = $this->build_branch(NULL, $taxonomy, $parts[1]);
                    }
                    break;

                default:
                    $tree = array();
                    break;
            }
        }

        return $tree;
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
     * Get Information about current post or page
     */

    protected function get_info() {
        global $wp_post_statuses, $wp_post_types;

        $id = intval($_POST['id']);
        $type = trim($_POST['type']);
        $role = $_POST['role'];
        $options = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'restrictions', array());

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
                        $exclude = ($options[$role]['posts'][$id]['exclude_page'] ? 'checked' : '');
                        $expire = ($options[$role]['posts'][$id]['expire'] ? date('m/d/Y', $options[$role]['posts'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###post_title###' => $this->pObj->edit_post_link($post),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
                        '###restrict_front_checked###' => (isset($checked_front) ? $checked_front : ''),
                        '###restrict_expire###' => (isset($expire) ? $expire : ''),
                        '###exclude_page_checked###' => (isset($exclude) ? $exclude : ''),
                        '###post_type###' => ucfirst($post->post_type),
                        '###post_status###' => $wp_post_statuses[$post->post_status]->label,
                        '###post_visibility###' => $this->pObj->check_visibility($post),
                        '###ID###' => $post->ID,
                    );
                    //check what type of post is it and render exclude if page
                    $render_exclude = FALSE;
                    if (isset($wp_post_types[$post->post_type])) {
                        switch ($wp_post_types[$post->post_type]->capability_type) {
                            case 'page':
                                $render_exclude = TRUE;
                                break;

                            default:
                                break;
                        }
                    }
                    if ($render_exclude) {
                        $excld_tmlp = $tmpl->retrieveSub('EXCLUDE_PAGE', $template);
                    } else {
                        $excld_tmlp = '';
                    }
                    $template = $tmpl->replaceSub('EXCLUDE_PAGE', $excld_tmlp, $template);

                    $result = array(
                        'status' => 'success',
                        'html' => $tmpl->updateMarkers($markerArray, $template)
                    );
                }
                break;

            case 'taxonomy':
                //get information about category
                $taxonomy = $this->pObj->get_taxonomy_by_term($id);
                $term = get_term($id, $taxonomy);
                if ($term->term_id) {
                    $template = $tmpl->retrieveSub('CATEGORY', $template);
                    if (isset($options[$role]['categories'][$id])) {
                        $checked = ($options[$role]['categories'][$id]['restrict'] ? 'checked' : '');
                        $checked_front = ($options[$role]['categories'][$id]['restrict_front'] ? 'checked' : '');
                        $expire = ($options[$role]['categories'][$id]['expire'] ? date('m/d/Y', $options[$role]['categories'][$id]['expire']) : '');
                    }
                    $markerArray = array(
                        '###name###' => $this->pObj->edit_term_link($term),
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

        return $result;
    }

    /*
     * Save information about page/post/category restriction
     * 
     */

    protected function save_info() {
        global $upgrade_restriction;

        $options = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'restrictions');
        if (!is_array($options)) {
            $options = array();
        }
        $result = array('status' => 'error');
        $id = intval($_POST['id']);
        $type = $_POST['type'];
        $role = $_POST['role'];
        $restrict = (isset($_POST['restrict']) ? 1 : 0);
        $restrict_front = (isset($_POST['restrict_front']) ? 1 : 0);
        $expire = $this->pObj->paserDate($_POST['restrict_expire']);
        $exclude = (isset($_POST['exclude_page']) ? 1 : 0);
        $apply = intval($_POST['apply']);
        $apply_all_cb = intval($_POST['apply_all_cb']);
        if ($apply == 1) {//apply for all roles
            $role_list = $this->pObj->get_roles();
        } else {
            $role_list = array($role => 1);
        }

        $this->pObj->update_blog_option(WPACCESS_PREFIX . 'hide_apply_all', $apply_all_cb);
        /*
         * Check if Restriction class exist.
         * Note for hacks : Better will be to buy an add-on for $5 because on
         * next release I'll change the checking class
         */
        $limit = WPACCESS_RESTRICTION_LIMIT;
        if (class_exists('aamer_aam_extend_restriction')) {
            $limit = apply_filters(WPACCESS_PREFIX . 'restrict_limit', $limit);
        }

        foreach ($role_list as $role => $dummy) {
            if ($role != WPACCESS_ADMIN_ROLE || $this->pObj->is_super) {
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
                                'expire' => $expire,
                                'exclude_page' => $exclude
                            );
                            $this->pObj->update_blog_option(WPACCESS_PREFIX . 'restrictions', $options);
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
                            $this->pObj->update_blog_option(WPACCESS_PREFIX . 'restrictions', $options);
                            $result = array('status' => 'success');
                        } else {
                            $result['message'] = $upgrade_restriction;
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        return $result;
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
        $response = $this->pObj->cURL($url, FALSE, TRUE);

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


        return $result;
    }

    /*
     * Save menu order
     * 
     */

    protected function save_order() {

        $apply_all = $_POST['apply_all'];
        $menu_order = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'menu_order');
        $roles = $this->pObj->get_roles();
        $role = $_POST['role'];

        if ($apply_all) {
            foreach ($roles as $role) {
                $menu_order[$role] = $_POST['menu'];
            }
        } else {
            if (isset($roles[$role])) {
                $menu_order[$role] = $_POST['menu'];
            }
        }

        $this->pObj->update_blog_option(WPACCESS_PREFIX . 'menu_order', $menu_order);

        return array('status' => 'success');
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
        $m = new module_optionManager($this->pObj);
        $m->render_config($file_path);

        return $file_path;
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

        return $data;
    }

    /*
     * Create super admin User Role
     */

    protected function create_super() {

        $answer = intval($_POST['answer']);

        if ($answer == 1) {
            $result = $this->create_role('Super Admin');

            if ($result['result'] == 'success') {
                $url = admin_url('user.php');
                $url = add_query_arg('page', 'wp_access', $url);
                $result['redirect'] = $url;
                //update current user role
                $user_id = get_current_user_id();
                $blog = $this->pObj->get_current_blog_data();
                $caps = get_usermeta($user_id, $blog['prefix'] . 'capabilities');
                $caps[$result['new_role']] = 1;
                update_usermeta($user_id, $blog['prefix'] . 'capabilities', $caps);
                //get all capability list and assign them to super admin role
                $roles = $this->pObj->get_roles(TRUE);
                $roles['super_admin'] = array(
                    'name' => 'Super Admin',
                    'capabilities' => $this->pObj->user->getAllCaps()
                );
                $this->pObj->update_blog_option('user_roles', $roles);
                $this->pObj->update_blog_option(WPACCESS_PREFIX . 'sa_dialog', $answer);
            }
        } else {
            $result = array('result' => 'success');
            $roles = $this->pObj->get_roles(TRUE);
            $roles[WPACCESS_ADMIN_ROLE]['capabilities']['aam_manage'] = 1; //add this role for admin automatically
            $this->pObj->update_blog_option('user_roles', $roles);
            $this->pObj->update_blog_option(WPACCESS_PREFIX . 'sa_dialog', $answer);
        }

        return $result;
    }

}

?>