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

/**
 * External API Class for AAM
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
final class mvb_Model_API {
    /**
     * No Restrictions
     */
    const RESTRICT_NO = 0;

    /**
     * Restrict Backend
     */
    const RESTRICT_BACK = 1;

    /**
     * Restrict Frontend
     */
    const RESTRICT_FRONT = 2;

    /**
     * Rescrict Both Sides
     */
    const RESTRICT_BOTH = 3;


    /**
     * Cache current blog object
     * 
     * @access protected
     * @var object
     */
    protected static $current_blog;

    /**
     * Cache Role List
     * 
     * @access protected
     * @var array
     */
    protected static $role_cache;

    /**
     * Check if is multisite network panel is used now
     * 
     * @see is_multisite(), is_network_admin()
     * @since version 1.5.5
     * @return bool
     */
    public static function isNetworkPanel() {

        return (is_multisite() && is_network_admin() ? TRUE : FALSE);
    }

    /**
     * Check if user is super admin
     * 
     * @since version 1.5.5
     * @param int $user_id
     * @return bool
     */
    public static function isSuperAdmin($user_id = FALSE) {

        $super = FALSE;
        $user_id = ($user_id ? $user_id : get_current_user_id());

        if (mvb_Model_API::isNetworkPanel() && is_super_admin($user_id)) {
            $super = TRUE;
        } elseif (!mvb_Model_API::isNetworkPanel()) {
            //check if user has a rule Super Admin
            $data = get_userdata($user_id);
            $cap_val = self::getCurrentBlog()->getPrefix() . 'capabilities';

            if (isset($data->{$cap_val}[WPACCESS_SADMIN_ROLE])) {
                $super = TRUE;
            } else {
                //check if answer is stored
                $answer = self::getBlogOption(WPACCESS_FTIME_MESSAGE, 0);
                if (!$answer) {
                    $super = TRUE;
                }
            }
        }

        return $super;
    }

    /**
     * Get Blog information
     * 
     * If it is not a multisite setup will return default blog object
     * 
     * @global object $wpdb
     * @param init $blog_id
     * @return object Return object mvb_Model_Blog or FALSE if blog not found
     */
    public static function getBlog($blog_id) {
        global $wpdb;

        $blog = FALSE;

        if (is_multisite()) {
            $query = "SELECT * FROM {$wpdb->blogs} WHERE blog_id = %d";
            $query = $wpdb->prepare($query, $blog_id);
            $data = $wpdb->get_row($query);

            if ($data) {
                $blog = new mvb_Model_Blog(array(
                            'id' => $data->blog_id,
                            'url' => get_site_url($data->blog_id),
                            'prefix' => $wpdb->get_blog_prefix($data->blog_id))
                );
            } else {
                Throw new Exception('Blog with ID ' . $blog_id . ' does not exist');
            }
        } else {
            $blog = new mvb_Model_Blog(array(
                        'id' => ($blog_id ? $blog_id : 1),
                        'url' => site_url(),
                        'prefix' => $wpdb->prefix)
            );
        }

        return $blog;
    }

    /**
     * Get Current Blog info
     * 
     * @return object 
     */
    public static function getCurrentBlog() {

        if (!self::$current_blog) {
            self::$current_blog = self::getBlog(get_current_blog_id());
        }

        return self::$current_blog;
    }
    
    /**
     * Set current blog
     * 
     * @param int $blog_id
     * @return bool
     */
    public static function setCurrentBlog($blog_id){
        
        if ($blog = self::getBlog($blog_id)){
            self::$current_blog = $blog;
        }
        
        return ($blog ? TRUE : FALSE);
    }

    /**
     * Get current blog's option
     * 
     * Check if multisite and execute a proper WP function to get option from DB
     * 
     * @global object $wpdb
     * @param string $option
     * @param mixed $default
     * @param object $blog
     * @return mixed 
     */
    public static function getBlogOption($option, $default = FALSE, $blog = FALSE) {
        global $wpdb;

        if (is_multisite()) {
            if (!($blog instanceof mvb_Model_Blog)) { //user current blog
                $blog = self::getCurrentBlog();
            }
            $result = get_blog_option($blog->getID(), $blog->getPrefix() . $option, $default);
        } else {
            $result = get_option($wpdb->prefix . $option, $default);
        }

        return $result;
    }

    /**
     * Get User Access Config Object
     * 
     * @param int $user_id
     * @param array $force_roles
     * @return object Return object mvb_Model_Config
     * @todo $user->ID should be change to something like $user->isError()
     */
    public static function getUserAccessConfig($user_id, $force_roles = FALSE) {

        $user = new mvb_Model_User($user_id);

        if (isset($user->ID)) {
            $user_roles = (is_array($force_roles) ? $force_roles : $user->getRoles());
            $conf = new mvb_Model_Config();

            foreach ($user_roles as $role) {
                $conf->mergeConfigs(self::getRoleAccessConfig($role));
            }

            //TODO - crap
            $u_conf = get_user_meta($user_id, WPACCESS_PREFIX . 'options', TRUE);

            if (!$u_conf){
                $u_conf = mvb_Model_Config::getDefaultConfig();
                $u_conf->menu = $conf->getMenu();
                $u_conf->metaboxes = $conf->getMetaboxes();
                $u_conf->capabilities = $conf->getCapabilities();
            }elseif(!is_object($u_conf)){
                $u_conf = (object) $u_conf;
            }
            
            $u_conf->restrictions = self::getRestrictions($user_id);
            $u_conf->menu_order = get_user_meta($user_id, WPACCESS_PREFIX . 'menu_order', TRUE);
            $conf->mergeConfigs(new mvb_Model_Config($u_conf), TRUE);
            
            //generate access config
            $conf->loadAccessConfig();
            
        } else {
            Throw new Exception('User with ID ' . $user_id . ' does not exist');
        }

        return $conf;
    }

    /**
     * Get User Role configuration
     * 
     * @param object $conf
     * @param string $role 
     */
    public static function getRoleAccessConfig($role) {

        if (!self::$role_cache) {
            $temp = self::getBlogOption(WPACCESS_PREFIX . 'options', array());
            $roles = self::getRoleList(TRUE);
            $menu_order = self::getBlogOption(WPACCESS_PREFIX . 'menu_order', array());

            foreach ($roles as $role_name => $data) {
                if (!isset($temp[$role_name])) {
                    $temp[$role_name] = mvb_Model_Config::getDefaultConfig();
                } elseif (!is_object($temp[$role_name])) {//TODO - Remove from commercial version
                    $temp[$role_name] = (object) $temp[$role_name];
                }
                $temp[$role_name]->capabilities = $data['capabilities'];
                $temp[$role_name]->restrictions = self::getRestrictions($role_name, 'role');
                $temp[$role_name]->menu_order = (isset($menu_order[$role_name]) ? $menu_order[$role_name] : array());
            }

            self::$role_cache = $temp;
        }

        //check if required role present
        if (!isset(self::$role_cache[$role])) {
            self::$role_cache[$role] = mvb_Model_Config::getDefaultConfig();
        }

        return new mvb_Model_Config(self::$role_cache[$role]);
    }

    /**
     * Get Restriction array
     * 
     * @param int $id 
     * @param string $type
     * @return array 
     */
    protected function getRestrictions($id, $type = 'user') {

        switch ($type) {
            case 'user':
                $rlist = get_user_meta($id, WPACCESS_PREFIX . 'restrictions', TRUE);
                $rlist = (is_array($rlist) ? $rlist : array());
                break;

            case 'role':
                if (isset(self::$role_cache[$id])) {
                    $rlist = self::$role_cache[$id]->restrictions;
                } else {
                    $rlist = self::getBlogOption(WPACCESS_PREFIX . 'restrictions');
                    $rlist = (isset($rlist[$id]) ? $rlist[$id] : array());
                }
                break;

            default:
                $rlist = array();
                break;
        }

        /*
         * Prepare list of all categories and subcategories
         * Why is this dynamically?
         * This part initiates each time because you can reogranize the
         * category tree after appling restiction to category
         */
        if (isset($rlist['categories']) && is_array($rlist['categories'])) {
            foreach ($rlist['categories'] as $cat_id => $restrict) {
                //now check combination of options
                $r = self::checkExpiration($restrict);
                if ($r) {
                    $rlist['categories'][$cat_id]['restrict'] = ($r & self::RESTRICT_BACK ? 1 : 0);
                    $rlist['categories'][$cat_id]['restrict_front'] = ($r & self::RESTRICT_FRONT ? 1 : 0);
                    //get list of all subcategories
                    $taxonomy = mvb_Model_Helper::getTaxonomyByTerm($cat_id);
                    $rlist['categories'][$cat_id]['taxonomy'] = $taxonomy;
                    $cat_list = get_term_children($cat_id, $taxonomy);
                    if (is_array($cat_list)) {
                        foreach ($cat_list as $cid) {
                            $rlist['categories'][$cid] = $rlist['categories'][$cat_id];
                        }
                    }
                } else {
                    $rlist['categories'][$cat_id]['restrict'] = 0;
                    $rlist['categories'][$cat_id]['restrict_front'] = 0;
                }
            }
        }
        //prepare list of posts and pages
        if (isset($rlist['posts']) && is_array($rlist['posts'])) {
            foreach ($rlist['posts'] as $post_id => $restrict) {
                //now check combination of options
                $r = self::checkExpiration($restrict);
                if ($r) {
                    $rlist['posts'][$post_id]['restrict'] = ($r & self::RESTRICT_BACK ? 1 : 0);
                    $rlist['posts'][$post_id]['restrict_front'] = ($r & self::RESTRICT_FRONT ? 1 : 0);
                } else {
                    if ($rlist['posts'][$post_id]['exclude_page']) {
                        $rlist['posts'][$post_id] = array(
                            'exclude_page' => 1
                        );
                    } else {
                        $rlist['posts'][$post_id]['restrict'] = 0;
                        $rlist['posts'][$post_id]['restrict_front'] = 0;
                    }
                }
            }
        }

        return $rlist;
    }

    /**
     * Get list of User Roles
     * 
     * Depending on $all parameter it'll return whole list of roles or filtered
     * 
     * @global object $wpdb
     * @param bool $filter
     * @return array 
     */
    public static function getRoleList($filter = TRUE) {
        global $wpdb;

        $roles = self::getBlogOption('user_roles', array());

        if ($filter) {
            //unset super admin role
            if (isset($roles[WPACCESS_SADMIN_ROLE])) {
                unset($roles[WPACCESS_SADMIN_ROLE]);
            }

            if (!mvb_Model_API::isSuperAdmin() && isset($roles[WPACCESS_ADMIN_ROLE])) {
                //exclude Administrator from list of allowed roles
                unset($roles[WPACCESS_ADMIN_ROLE]);
            }
        }

        return $roles;
    }

    /**
     * Check if access is expired according to date
     * 
     * @param array $data
     * @return int 
     */
    public static function checkExpiration($data) {

        $result = self::RESTRICT_NO;
        if (($data['restrict'] || $data['restrict_front']) && !trim($data['expire'])) {
            $result = ($data['restrict'] ? $result | self::RESTRICT_BACK : $result);
            $result = ($data['restrict_front'] ? $result | self::RESTRICT_FRONT : $result);
        } elseif (($data['restrict'] || $data['restrict_front']) && trim($data['expire'])) {
            if ($data['expire'] >= time()) {
                $result = ($data['restrict'] ? $result | self::RESTRICT_BACK : $result);
                $result = ($data['restrict_front'] ? $result | self::RESTRICT_FRONT : $result);
            }
        } elseif (trim($data['expire'])) {
            if (time() <= $data['expire']) {
                $result = self::RESTRICT_BOTH; //TODO - Think about it
            }
        }

        return $result;
    }

    /**
     * Update Blog Option
     * 
     * If $blog is not specified, will user current blog
     * 
     * @global object $wpdb
     * @param string $option
     * @param mixed $data
     * @param object $blog
     * @return bool 
     */
    public static function updateBlogOption($option, $data, $blog = FALSE) {
        global $wpdb;

        if (is_multisite()) {
            if ($blog === FALSE) { //user current blog
                $blog = self::getCurrentBlog();
            }
            $result = update_blog_option($blog->getID(), $blog->getPrefix() . $option, $data);
        } else {
            $result = update_option($wpdb->prefix . $option, $data);
        }

        return $result;
    }

    /**
     * Delete Blog Option
     * 
     * @global object $wpdb
     * @param string $option
     * @param object $blog
     * @return bool
     */
    public static function deleteBlogOption($option, $blog = FALSE) {
        global $wpdb;

        if (is_multisite()) {
            if ($blog === FALSE) { //user current blog
                $blog = self::getCurrentBlog();
            }
            $result = delete_blog_option($blog->getID(), $blog->getPrefix() . $option);
        } else {
            $result = delete_option($wpdb->prefix . $option);
        }

        return $result;
    }

}

?>