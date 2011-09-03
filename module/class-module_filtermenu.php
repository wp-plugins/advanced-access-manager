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

class module_filterMenu extends module_User {

    private $cParams;

    function __construct() {

        parent::__construct();

        $this->cParams = get_option(WPACCESS_PREFIX . 'options');
    }

    function manage() {
        global $menu, $submenu;

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
            wp_die('You are not authorized to view this page');
        }
    }

    //TODO - This is a copy from optionmanager

    protected function getRoleMenu($c_role) {
        global $menu;

        $menu_order = get_option(WPACCESS_PREFIX . 'menu_order');

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

    function checkAccess($requestedMenu) {

        $userRoles = $this->getCurrentUserRole();

        if (is_array($userRoles)) {
            foreach ($userRoles as $role) {
                if ($role == WPACCESS_ADMIN_ROLE) {
                    return TRUE;
                }

                if (isset($this->cParams[$role]['menu']) && is_array($this->cParams[$role]['menu'])) {
                    foreach ($this->cParams[$role]['menu'] as $menu => $sub) {
                        if (isset($sub['whole']) && ($sub['whole'] == 1) && ($this->compareMenus($requestedMenu, $menu))) {
                            return FALSE;
                        }

                        if (isset($sub['sub']) && is_array($sub['sub'])) {
                            foreach ($sub['sub'] as $subMenu => $dummy) {
                                if ($this->compareMenus($requestedMenu, $subMenu)) {
                                    return FALSE;
                                }
                            }
                        }
                    }
                }
            }
        }

        return TRUE;
    }

    function compareMenus($requestedMenu, $menu) {
        $result = FALSE;

        $parts = preg_split('/\?/', $menu);
        if (count($parts) == 2) {
            $params = preg_split('/&/', $parts[1]);
            if (is_array($params) && (strpos($requestedMenu, $parts[0]) !== FALSE)) {
                foreach ($params as $param) {
                    if (strpos($requestedMenu, $param) !== FALSE) {
                        $result = TRUE;
                        break;
                    }
                }
            }
        } else {
            if (strpos($requestedMenu, $parts[0]) !== FALSE) {
                $result = TRUE;
            }
            //TODO - Emergency solution
            if (isset($_REQUEST['post_type']) && ($_REQUEST['post_type'] != 'post')
                    && in_array($menu, array('edit.php', 'post-new.php'))) {
                $result = FALSE;
            }
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