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

class module_User extends WP_User {

    function __construct($pObj, $user_id = FALSE) {

        $this->pObj = $pObj;
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        parent::__construct($user_id);
    }

    function getCurrentUserRole() {
        global $wp_version;

        if (version_compare($wp_version, '3.3', '=')) {
            $result = (is_array($this->roles) ? $this->roles : array());
        } else {
            //deprecated, will be deleted in release 1.5
            if (is_object($this->data) && isset($this->data->{$this->cap_key})) {
                $result = array_keys($this->data->{$this->cap_key});
            } else {
                $result = array();
            }
        }

        return $result;
    }

    function getAllCaps() {

        $caps = $this->allcaps;
        $caps = (is_array($caps) ? $caps : array());

        $unset_list = array(WPACCESS_SADMIN_ROLE);
        foreach ($unset_list as $unset) {
            if (isset($caps[$unset])) {
                unset($caps[$unset]);
            }
        }

        return $caps;
    }

    function getCapabilityHumanTitle($cap) {

        $title = array();
        $parts = preg_split('/_/', $cap);
        if (is_array($parts)) {
            foreach ($parts as &$part) {
                $part = ucfirst($part);
            }
        }

        return implode(' ', $parts);
    }

}

?>