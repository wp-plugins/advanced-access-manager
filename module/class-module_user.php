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

    function __construct() {

        parent::__construct(get_current_user_id());
    }

    function getCurrentUserRole() {

        if (is_object($this->data) && is_array($this->data->{$this->cap_key})) {
            $result = array_keys($this->data->{$this->cap_key});
        } else {
            $result = array();
        }

        return $result;
    }

    function getAllCaps() {

        return $this->allcaps;
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