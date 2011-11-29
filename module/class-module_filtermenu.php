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

/*
 * Filter Main Menu and control access to resources
 * 
 * @package AAM
 * @subpackage Module
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 */

class module_filterMenu extends module_User {

    private $cParams;

    /*
     * Main Object
     * 
     * @var object
     * @access protected
     */
    protected $pObj;

    function __construct($pObj) {

        $this->pObj = $pObj;
        parent::__construct($pObj);

        $this->cParams = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'options', array());
        $keyParams = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'key_params', array());
        $keyParams = (is_array($keyParams) ? $keyParams : array());

        $this->keyParams = array_keys($keyParams); //TODO - Save in array format
    }

    function manage() {
        global $menu, $submenu, $restrict_message;

        $userRoles = $this->getCurrentUserRole();

        if (is_array($userRoles)) {
            foreach ($userRoles as $role) {
                if (isset($this->cParams[$role]['menu']) && is_array($this->cParams[$role]['menu'])) {
                    foreach ($this->cParams[$role]['menu'] as $main => $data) {
                        if (isset($data['whole']) && ($data['whole'] == 1)) {
                            $this->unsetMainMenuItem($main);
                        } elseif (isset($data['sub']) && is_array($data['sub'])) {
                            foreach ($data['sub'] as $sub => $dummy) {
                                $this->unsetSubMenuItem($main, $sub);
                            }
                        }
                    }
                }
            }

            $menu = $this->getRoleMenu($userRoles[0]);
        } else {
            do_action(WPACCESS_PREFIX . 'admin_redirect');
            wp_die($restrict_message);
        }
    }

    //TODO - This is a copy from optionmanager

    protected function getRoleMenu($c_role) {
        global $menu;

        $menu_order = $this->pObj->get_blog_option(WPACCESS_PREFIX . 'menu_order', array());

        $r_menu = $menu;
        ksort($r_menu);

        if (isset($menu_order[$c_role]) && is_array($menu_order[$c_role])) {//reorganize menu according to role
            if (is_array($menu)) {
                $w_menu = array();
                foreach ($menu_order[$c_role] as $mid) {
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
                // debug($r_menu);
            }
        }

        return $r_menu;
    }

    /*
     * Check if User has Access to current page
     * 
     * @param string Current Requested URI
     * @return bool TRUE if access granded
     */

    function checkAccess($requestedMenu) {

        if (!$this->pObj->is_super) {
            $userRoles = $this->getCurrentUserRole();
            if (is_array($userRoles)) {
                //get base file
                $parts = $this->get_parts($requestedMenu);
                foreach ($userRoles as $role) {
                    //aam_debug($this->cParams[$role]['menu']);
                    if (isset($this->cParams[$role]['menu']) && is_array($this->cParams[$role]['menu'])) {
                        foreach ($this->cParams[$role]['menu'] as $menu => $sub) {
                            if ($this->compareMenus($parts, $menu) && isset($sub['whole'])) {
                                return FALSE;
                            }
                            if (isset($sub['sub']) && is_array($sub['sub'])) {
                                foreach ($sub['sub'] as $subMenu => $dummy) {
                                    if ($this->compareMenus($parts, $subMenu)) {
                                        return FALSE;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return TRUE;
    }

    function compareMenus($parts, $menu) {

        $compare = $this->get_parts($menu);
        $c_params = array_intersect($parts, $compare);
        $result = FALSE;
/*
        aam_debug($parts);
        aam_debug($menu);
        aam_debug($compare);
        aam_debug($c_params);
*/
        if (count($c_params) == count($parts)) { //equal menus
            $result = TRUE;
        } elseif (count($c_params) && ($parts[0] == $compare[0])) { //probably similar
            
            $diff = array_diff($parts, $compare) + array_diff($compare, $parts);
            $result = TRUE;
            
            foreach ($diff as $d) {
                $td = preg_split('/=/', $d);
                if (in_array($td[0], $this->keyParams)) {
                    $result = FALSE;
                    break;
                }
            }
        }

        return $result;
    }

    function get_parts($requestedMenu) {

        //this is for only one case - edit.php
        if (basename($requestedMenu) == 'edit.php') {
            $requestedMenu .= '?post_type=post';
        }
        //splite requested URI
        $parts = preg_split('/\?/', $requestedMenu);
        $result = array(basename($parts[0]));

        if (count($parts) > 1) { //no parameters
            $params = preg_split('/&|&amp;/', $parts[1]);
            $result = array_merge($result, $params);
        }

        return $result;
    }

    function unsetMainMenuItem($menuItem) {
        global $menu, $submenu;

        if (is_array($menu)) {
            foreach ($menu as $key => $item) {
                if ($item[2] == $menuItem) {
                    unset($menu[$key]);
                    unset($submenu[$menuItem]);
                }
            }
        }
    }

    function unsetSubMenuItem($dummy, $submenuItem) {
        global $submenu;

        $result = FALSE; //not deleted
        if (is_array($submenu)) {
            foreach ($submenu as $main => $subs) {
                if (is_array($subs)) {
                    foreach ($subs as $key => $item) {
                        if ($item[2] == $submenuItem) {
                            unset($submenu[$main][$key]);
                            return TRUE;
                        }
                    }
                }
            }
        }

        return $result;
    }

}

?>