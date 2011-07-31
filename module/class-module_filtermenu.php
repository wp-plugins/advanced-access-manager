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
                if (is_array($this->cParams[$role])) {
                    foreach ($menu as $key => $menuItem) {
                        if (is_array($this->cParams[$role]['menu'][$menuItem[2]])) {
                            if ($this->cParams[$role]['menu'][$menuItem[2]]['whole']) {
                                $this->unsetMainMenuItem($menuItem[2]);
                            } elseif (is_array($this->cParams[$role]['menu'][$menuItem[2]]['sub'])) {
                                if (is_array($submenu[$menuItem[2]])) {
                                    foreach ($submenu[$menuItem[2]] as $subkey => $submenuItem) {
                                        if (isset($this->cParams[$role]['menu'][$menuItem[2]]['sub'][$submenuItem[2]])) {
                                            $this->unsetSubMenuItem($menuItem[2], $submenuItem[2]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            wp_die('You are not authorized to view this page');
        }
    }

    function checkAccess($requestedMenu) {

        $userRoles = $this->getCurrentUserRole();

        if (is_array($userRoles)) {
            foreach ($userRoles as $role) {
                if ($role == 'administrator') {
                    return TRUE;
                }
                if (is_array($this->cParams[$role]['menu'])) {
                    foreach ($this->cParams[$role]['menu'] as $menu => $sub) {
                        if ($this->compareMenus($requestedMenu, $menu)) {
                            return FALSE;
                        }
                        if (is_array($sub['sub'])) {
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

    /*
      function _compareMenus($requestedMenu, $menu) {
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
      }

      return $result;
      }
     */

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

    function unsetSubMenuItem($menuItem, $submenuItem) {
        global $submenu;

        if (is_array($submenu[$menuItem])) {
            foreach ($submenu[$menuItem] as $key => $item) {
                if ($item[2] == $submenuItem) {
                    unset($submenu[$menuItem][$key]);
                }
            }
        }
    }

}

?>