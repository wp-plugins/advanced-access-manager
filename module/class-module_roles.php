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

class module_Roles extends WP_Roles {

    function __construct() {

        parent::__construct();
    }

    /*
     * Create a New User's Role
     * 
     * Use add_role function from WP_Roles to create a new User Role
     * 
     * @param string User Role title
     * @return array Result
     */

    function createNewRole($newRoleTitle) {
        global $table_prefix, $defCapabilities;

        if (preg_match('/^[a-z0-9\s]{1,}$/i', trim($newRoleTitle))) {
            $newRole = strtolower(str_replace(' ', '_', $newRoleTitle));
            if ($this->add_role($newRole, $newRoleTitle, $defCapabilities)) {
                $status = 'success';
            } else {
                $status = 'error';
            }
        } else {
            //TODO - Now only one error appears on front - "Already exists"
            $status = 'error';
        }

        $result = array(
            'result' => $status,
            'new_role' => $newRole,
        );

        return $result;
    }

}

?>