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

class module_filterMetabox extends module_User {

    private $cParams;

    function __construct() {

        parent::__construct();

        $this->cParams = get_option(WPACCESS_PREFIX . 'options');
    }

    function manage($area = 'post') {
        global $wp_meta_boxes, $post;

        $userRoles = $this->getCurrentUserRole();

        if (is_array($userRoles)) {
            foreach ($userRoles as $role) {
                //   debug($this->cParams[$role]);
                if (isset($this->cParams[$role]) && is_array($this->cParams[$role]['metaboxes'])) {
                    switch ($area) {
                        case 'dashboard':
                            if (is_array($wp_meta_boxes['dashboard'])) {
                                foreach ($wp_meta_boxes['dashboard'] as $position => $metaboxes) {
                                    foreach ($metaboxes as $priority => $metaboxes1) {
                                        foreach ($metaboxes1 as $metabox => $data) {
                                            if (isset($this->cParams[$role]['metaboxes']['dashboard-' . $metabox])) {
                                                unset($wp_meta_boxes['dashboard'][$position][$priority][$metabox]);
                                            }
                                        }
                                    }
                                }
                            }
                            break;

                        default:
                            if ($wp_meta_boxes[$post->post_type]) {
                                foreach ($wp_meta_boxes[$post->post_type] as $position => $metaboxes) {
                                    foreach ($metaboxes as $priority => $metaboxes1) {
                                        foreach ($metaboxes1 as $metabox => $data) {
                                            if (isset($this->cParams[$role]['metaboxes'][$post->post_type . '-' . $metabox])) {
                                                unset($wp_meta_boxes[$post->post_type][$position][$priority][$metabox]);
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        } else {
            wp_die('You are not authorized to view this page');
        }
    }

}

?>